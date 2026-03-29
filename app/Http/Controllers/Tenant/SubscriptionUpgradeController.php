<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Services\PayMongoService;
use App\Services\TenantFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionUpgradeController extends Controller
{
    public function __construct(
        protected PayMongoService $paymongo,
        protected TenantFeatureService $featureService,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $planId = $request->query('plan');
        
        if (!$planId) {
            return redirect()->route('tenant.subscription.plans');
        }

        $plan = SubscriptionPlan::find($planId);
        
        if (!$plan || $plan->isFree()) {
            return redirect()->route('tenant.subscription.plans')
                ->with('error', 'Invalid plan selected.');
        }

        $tenant = tenant();
        $tenant->load('subscriptionPlan');

        if ($tenant->subscriptionPlan && $tenant->subscriptionPlan->id === $plan->id) {
            return redirect()->route('tenant.subscription')
                ->with('info', 'You are already on this plan.');
        }

        return view('tenant.subscription-upgrade', [
            'tenant' => $tenant,
            'currentPlan' => $tenant->subscriptionPlan,
            'newPlan' => $plan,
            'shopName' => $tenant->data['shop_name'] ?? $tenant->id,
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $planId = $request->input('plan_id');
        
        $plan = SubscriptionPlan::find($planId);
        
        if (!$plan || $plan->isFree()) {
            return redirect()->route('tenant.subscription.plans')
                ->with('error', 'Invalid plan selected.');
        }

        $tenant = tenant();
        $tenant->load('subscriptionPlan');

        if ($tenant->subscriptionPlan && $tenant->subscriptionPlan->id === $plan->id) {
            return redirect()->route('tenant.subscription')
                ->with('info', 'You are already on this plan.');
        }

        // Check existing pending upgrade payments
        $existingPayments = Payment::where('tenant_id', $tenant->id)
            ->where('payment_type', 'upgrade')
            ->where('subscription_plan_id', $plan->id)
            ->where('status', 'pending')
            ->whereNotNull('paymongo_checkout_id')
            ->get();

        $activeCheckoutUrl = null;

        foreach ($existingPayments as $existingPayment) {
            try {
                $sessionStatus = $this->paymongo->getCheckoutSessionStatus(
                    $existingPayment->paymongo_checkout_id
                );

                if ($sessionStatus['status'] === 'succeeded' || $sessionStatus['link_status'] === 'paid') {
                    $existingPayment->update([
                        'status' => 'paid',
                        'payment_method' => $sessionStatus['payment_method'],
                        'paymongo_payment_id' => $sessionStatus['payment_id'],
                        'paid_at' => now(),
                    ]);

                    $this->upgradePlan($tenant, $plan->id);

                    return redirect()->route('tenant.subscription')
                        ->with('success', 'Plan upgraded successfully!');
                }

                if ($sessionStatus['link_status'] === 'active' && $sessionStatus['checkout_url']) {
                    $activeCheckoutUrl = $sessionStatus['checkout_url'];
                }
            } catch (\Exception) {
                continue;
            }
        }

        if ($activeCheckoutUrl !== null) {
            return redirect()->away($activeCheckoutUrl);
        }

        $amountInCentavos = (int) ($plan->price * 100);
        $shopName = $tenant->data['shop_name'] ?? $tenant->id;
        $domain = $tenant->domains->first()?->domain ?? "{$tenant->id}.localhost";

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'payment_type' => 'upgrade',
            'amount' => $plan->price,
            'currency' => 'PHP',
            'status' => 'pending',
            'description' => "Plan Upgrade — {$plan->name} Plan — {$shopName}",
        ]);

        try {
            $checkout = $this->paymongo->createCheckoutSession([
                'amount' => $amountInCentavos,
                'currency' => 'PHP',
                'description' => "Plan Upgrade — {$plan->name} Plan — {$shopName}",
                'success_url' => "http://{$domain}:8000/subscription/upgrade/success?payment_id={$payment->id}",
                'cancel_url' => "http://{$domain}:8000/subscription/upgrade?plan={$plan->id}",
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'payment_id' => $payment->id,
                    'plan_id' => $plan->id,
                    'payment_type' => 'upgrade',
                ],
            ]);

            $payment->update([
                'paymongo_checkout_id' => $checkout['id'],
            ]);

            return redirect()->away($checkout['checkout_url']);
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);

            return back()->with('error', 'Unable to create checkout session. Please try again.');
        }
    }

    public function success(Request $request): View|RedirectResponse
    {
        $tenant = tenant();
        $paymentId = $request->query('payment_id');

        if (!$paymentId) {
            return redirect()->route('tenant.subscription.plans');
        }

        $payment = Payment::where('id', $paymentId)
            ->where('tenant_id', $tenant->id)
            ->where('payment_type', 'upgrade')
            ->first();

        if (!$payment) {
            return redirect()->route('tenant.subscription.plans');
        }

        if ($payment->paymongo_checkout_id && $payment->isPending()) {
            try {
                $sessionStatus = $this->paymongo->getCheckoutSessionStatus(
                    $payment->paymongo_checkout_id
                );

                if ($sessionStatus['status'] === 'succeeded'
                    || $sessionStatus['link_status'] === 'paid'
                ) {
                    $payment->update([
                        'status' => 'paid',
                        'payment_method' => $sessionStatus['payment_method'],
                        'paymongo_payment_id' => $sessionStatus['payment_id'],
                        'paid_at' => now(),
                    ]);

                    $this->upgradePlan($tenant, $payment->subscription_plan_id);
                }
            } catch (\Exception) {
                // Webhook will handle it
            }
        }

        if ($payment->isPaid()) {
            $tenant->refresh();
            $tenant->load('subscriptionPlan');
            
            return view('tenant.subscription-upgrade-success', [
                'tenant' => $tenant,
                'payment' => $payment,
                'plan' => $tenant->subscriptionPlan,
            ]);
        }

        return view('tenant.payment-pending', [
            'tenant' => $tenant,
            'payment' => $payment,
        ]);
    }

    protected function upgradePlan($tenant, $planId): void
    {
        $plan = SubscriptionPlan::find($planId);
        
        if (!$plan) {
            return;
        }

        $newExpirationDate = match ($plan->billing_cycle) {
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };

        $normalizedFeatures = $this->featureService->normalize($plan->features ?? []);
        
        // Direct assignment and save
        $tenant->subscription_plan_id = $plan->id;
        $tenant->features = $normalizedFeatures;
        $tenant->is_paid = true;
        $tenant->is_enabled = true;
        $tenant->subscription_expires_at = $newExpirationDate;
        $tenant->last_renewal_reminder_sent_at = null;
        $saved = $tenant->save();
        
        \Log::info('Tenant upgrade attempt', [
            'tenant_id' => $tenant->id,
            'new_plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'plan_features' => $plan->features,
            'normalized_features' => $normalizedFeatures,
            'save_result' => $saved,
            'tenant_features_after' => $tenant->features,
            'tenant_plan_id_after' => $tenant->subscription_plan_id,
        ]);
    }
}
