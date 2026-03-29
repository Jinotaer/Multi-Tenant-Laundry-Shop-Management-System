<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PayMongoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionRenewalController extends Controller
{
    public function __construct(
        protected PayMongoService $paymongo,
    ) {}

    /**
     * Show the subscription renewal page.
     */
    public function show(): View|RedirectResponse
    {
        $tenant = tenant();
        $tenant->load('subscriptionPlan');

        if ($tenant->is_paid && !$tenant->needsRenewal()) {
            return redirect()->route('tenant.subscription')
                ->with('success', 'Your subscription is already active.');
        }

        return view('tenant.subscription-renewal', [
            'tenant' => $tenant,
            'plan' => $tenant->subscriptionPlan,
            'shopName' => $tenant->data['shop_name'] ?? $tenant->id,
            'isInGracePeriod' => $tenant->isInGracePeriod(),
            'graceDaysRemaining' => $tenant->graceDaysRemaining(),
        ]);
    }

    /**
     * Create a PayMongo checkout session for renewal.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $tenant = tenant();
        $tenant->load('subscriptionPlan');
        $plan = $tenant->subscriptionPlan;

        if (!$plan || $plan->isFree()) {
            return redirect()->route('tenant.subscription')
                ->with('error', 'No payment required for free plans.');
        }

        if ($tenant->is_paid && !$tenant->needsRenewal()) {
            return redirect()->route('tenant.subscription');
        }

        // Check existing pending renewal payments
        $existingPayments = Payment::where('tenant_id', $tenant->id)
            ->where('payment_type', 'renewal')
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

                    $this->activateSubscription($tenant);

                    return redirect()->route('tenant.subscription')
                        ->with('success', 'Subscription renewed successfully!');
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

        // Create new renewal payment
        $amountInCentavos = (int) ($plan->price * 100);
        $shopName = $tenant->data['shop_name'] ?? $tenant->id;
        $domain = $tenant->domains->first()?->domain ?? "{$tenant->id}.localhost";

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'payment_type' => 'renewal',
            'amount' => $plan->price,
            'currency' => 'PHP',
            'status' => 'pending',
            'description' => "Subscription Renewal — {$plan->name} Plan — {$shopName}",
        ]);

        try {
            $checkout = $this->paymongo->createCheckoutSession([
                'amount' => $amountInCentavos,
                'currency' => 'PHP',
                'description' => "Subscription Renewal — {$plan->name} Plan — {$shopName}",
                'success_url' => "http://{$domain}:8000/subscription/renew/success?payment_id={$payment->id}",
                'cancel_url' => "http://{$domain}:8000/subscription/renew",
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'payment_id' => $payment->id,
                    'plan_id' => $plan->id,
                    'payment_type' => 'renewal',
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

    /**
     * Handle successful renewal payment.
     */
    public function success(Request $request): View|RedirectResponse
    {
        $tenant = tenant();
        $paymentId = $request->query('payment_id');

        if (!$paymentId) {
            return redirect()->route('tenant.subscription.renew');
        }

        $payment = Payment::where('id', $paymentId)
            ->where('tenant_id', $tenant->id)
            ->where('payment_type', 'renewal')
            ->first();

        if (!$payment) {
            return redirect()->route('tenant.subscription.renew');
        }

        if ($payment->paymongo_checkout_id && $payment->isPending()) {
            try {
                $sessionStatus = $this->paymongo->getCheckoutSessionStatus(
                    $payment->paymongo_checkout_id
                );

                if ($sessionStatus['status'] === 'succeeded'
                    || $sessionStatus['link_status'] === 'active'
                    || $sessionStatus['link_status'] === 'paid'
                ) {
                    $payment->update([
                        'status' => 'paid',
                        'payment_method' => $sessionStatus['payment_method'],
                        'paymongo_payment_id' => $sessionStatus['payment_id'],
                        'paid_at' => now(),
                    ]);

                    $this->activateSubscription($tenant);
                }
            } catch (\Exception) {
                // Webhook will handle it
            }
        }

        if ($payment->isPaid() || $tenant->is_paid) {
            return view('tenant.subscription-renewal-success', [
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

    /**
     * Activate subscription after successful payment.
     */
    protected function activateSubscription($tenant): void
    {
        $plan = $tenant->subscriptionPlan;
        
        if (!$plan) {
            return;
        }

        $newExpirationDate = match ($plan->billing_cycle) {
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };

        $tenant->update([
            'is_paid' => true,
            'is_enabled' => true,
            'subscription_expires_at' => $newExpirationDate,
            'last_renewal_reminder_sent_at' => null,
        ]);
    }
}
