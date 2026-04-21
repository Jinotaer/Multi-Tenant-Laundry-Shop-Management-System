<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PayMongoService;
use App\Services\TenantMetricService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionDowngradeController extends Controller
{
    public function __construct(
        protected PayMongoService $paymongo,
        protected TenantMetricService $tenantMetricService,
    ) {}

    /**
     * Show the subscription downgrade page with plan selection.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $tenant = tenant();
        $tenant->load('subscriptionPlan');

        // Must have expired subscription to downgrade
        if ($tenant->is_paid && !$tenant->needsRenewal()) {
            return redirect()->route('tenant.subscription')
                ->with('error', 'You can only change plans when your subscription expires.');
        }

        $currentPlan = $tenant->subscriptionPlan;
        
        // Get all active plans except the current one
        $availablePlans = SubscriptionPlan::where('is_active', true)
            ->where('id', '!=', $currentPlan->id)
            ->orderBy('price', 'desc')
            ->get();

        if ($availablePlans->isEmpty()) {
            return redirect()->route('tenant.subscription.renew')
                ->with('error', 'No other plans available.');
        }

        // Get current usage statistics
        $currentUsage = $this->getCurrentUsage($tenant);

        // Check which plans are compatible with current usage
        $plansWithCompatibility = $availablePlans->map(function ($plan) use ($currentUsage, $currentPlan) {
            // Upgrades (higher price) are always compatible
            $isUpgrade = $plan->price > $currentPlan->price;
            $plan->is_compatible = $isUpgrade || $this->checkPlanCompatibility($plan, $currentUsage);
            $plan->compatibility_issues = $isUpgrade ? [] : $this->getCompatibilityIssues($plan, $currentUsage);
            return $plan;
        });

        return view('tenant.subscription-downgrade', [
            'tenant' => $tenant,
            'currentPlan' => $currentPlan,
            'availablePlans' => $plansWithCompatibility,
            'currentUsage' => $currentUsage,
            'shopName' => $tenant->data['shop_name'] ?? $tenant->id,
            'isInGracePeriod' => $tenant->isInGracePeriod(),
            'graceDaysRemaining' => $tenant->graceDaysRemaining(),
        ]);
    }

    /**
     * Show downgrade confirmation page for specific plan.
     */
    public function confirm(Request $request, SubscriptionPlan $plan): View|RedirectResponse
    {
        $tenant = tenant();
        $tenant->load('subscriptionPlan');
        $currentPlan = $tenant->subscriptionPlan;

        // Validate it's a different plan
        if ($plan->id === $currentPlan->id) {
            return redirect()->route('tenant.subscription.change-plan')
                ->with('error', 'This is your current plan.');
        }

        $currentUsage = $this->getCurrentUsage($tenant);
        $hasExcessUsage = !$this->checkPlanCompatibility($plan, $currentUsage);
        $issues = $this->getCompatibilityIssues($plan, $currentUsage);

        return view('tenant.subscription-downgrade-confirm', [
            'tenant' => $tenant,
            'currentPlan' => $currentPlan,
            'newPlan' => $plan,
            'currentUsage' => $currentUsage,
            'isCompatible' => !$hasExcessUsage,
            'hasExcessUsage' => $hasExcessUsage,
            'compatibilityIssues' => $issues,
            'shopName' => $tenant->data['shop_name'] ?? $tenant->id,
        ]);
    }

    /**
     * Process downgrade checkout.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $request->validate([
            'plan_id' => 'required|integer',
            'confirmation' => 'required|in:DOWNGRADE,UPGRADE',
            'acknowledge_limits' => 'nullable|boolean',
        ]);

        $tenant = tenant();
        $tenant->load('subscriptionPlan');
        $currentPlan = $tenant->subscriptionPlan;
        
        // Get plan from central database
        $newPlan = \Illuminate\Support\Facades\DB::connection('central')
            ->table('subscription_plans')
            ->where('id', $request->plan_id)
            ->first();

        if (!$newPlan) {
            return back()->with('error', 'Selected plan not found.');
        }

        // Convert to SubscriptionPlan model
        $newPlan = SubscriptionPlan::on('central')->find($request->plan_id);

        // Validate it's a different plan
        if ($newPlan->id === $currentPlan->id) {
            return back()->with('error', 'This is your current plan.');
        }

        // Check if downgrading with excess usage
        $currentUsage = $this->getCurrentUsage($tenant);
        $isUpgrade = $newPlan->price > $currentPlan->price;
        $hasExcessUsage = !$isUpgrade && !$this->checkPlanCompatibility($newPlan, $currentUsage);
        
        // If downgrading with excess usage, require acknowledgment
        if ($hasExcessUsage && !$request->boolean('acknowledge_limits')) {
            return back()->with('error', 'You must acknowledge the plan limits before proceeding.');
        }

        // If free plan, activate immediately
        if ($newPlan->isFree()) {
            $tenant->update([
                'subscription_plan_id' => $newPlan->id,
                'is_paid' => true,
                'is_enabled' => true,
                'subscription_expires_at' => null,
            ]);

            return redirect()->route('tenant.subscription.change-plan.success', ['plan' => $newPlan->id]);
        }

        // Create payment for paid plan
        $amountInCentavos = (int) ($newPlan->price * 100);
        $shopName = $tenant->data['shop_name'] ?? $tenant->id;
        $domain = $tenant->domains->first()?->domain ?? "{$tenant->id}.localhost";

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $newPlan->id,
            'payment_type' => 'downgrade',
            'amount' => $newPlan->price,
            'currency' => 'PHP',
            'status' => 'pending',
            'description' => "Subscription Plan Change — {$newPlan->name} Plan — {$shopName}",
        ]);

        try {
            $checkout = $this->paymongo->createCheckoutSession([
                'amount' => $amountInCentavos,
                'currency' => 'PHP',
                'description' => "Subscription Plan Change — {$newPlan->name} Plan — {$shopName}",
                'success_url' => "http://{$domain}:8000/subscription/change-plan/success?payment_id={$payment->id}",
                'cancel_url' => "http://{$domain}:8000/subscription/change-plan/confirm/{$newPlan->id}",
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'payment_id' => $payment->id,
                    'plan_id' => $newPlan->id,
                    'payment_type' => 'downgrade',
                    'old_plan_id' => $currentPlan->id,
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
     * Handle successful downgrade payment.
     */
    public function success(Request $request): View|RedirectResponse
    {
        $tenant = tenant();
        $paymentId = $request->query('payment_id');

        if (!$paymentId) {
            return redirect()->route('tenant.subscription.change-plan');
        }

        $payment = Payment::where('id', $paymentId)
            ->where('tenant_id', $tenant->id)
            ->where('payment_type', 'downgrade')
            ->first();

        if (!$payment) {
            return redirect()->route('tenant.subscription.change-plan');
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

                    $this->activateDowngrade($tenant, $payment->subscription_plan_id);
                }
            } catch (\Exception) {
                // Webhook will handle it
            }
        }

        $newPlan = SubscriptionPlan::on('central')->find($payment->subscription_plan_id);

        if ($payment->isPaid() || $tenant->is_paid) {
            return view('tenant.subscription-downgrade-success', [
                'tenant' => $tenant,
                'payment' => $payment,
                'plan' => $newPlan,
            ]);
        }

        return view('tenant.payment-pending', [
            'tenant' => $tenant,
            'payment' => $payment,
        ]);
    }

    /**
     * Activate downgraded subscription.
     */
    protected function activateDowngrade($tenant, $newPlanId): void
    {
        $newPlan = SubscriptionPlan::on('central')->find($newPlanId);

        if (!$newPlan) {
            return;
        }

        $newExpirationDate = match ($newPlan->billing_cycle) {
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };

        $tenant->update([
            'subscription_plan_id' => $newPlanId,
            'is_paid' => true,
            'is_enabled' => true,
            'subscription_expires_at' => $newExpirationDate,
            'last_renewal_reminder_sent_at' => null,
            'features' => $newPlan->features, // Update features to match new plan
        ]);

        $this->tenantMetricService->resetMonthlyUsage($tenant);
    }

    /**
     * Get current tenant usage statistics.
     */
    protected function getCurrentUsage($tenant): array
    {
        return [
            'staff_count' => User::count(),
            'customer_count' => Customer::count(),
            'order_count' => \App\Models\Order::whereMonth('created_at', now()->month)->count(),
        ];
    }

    /**
     * Check if plan is compatible with current usage.
     */
    protected function checkPlanCompatibility(SubscriptionPlan $plan, array $usage): bool
    {
        if ($plan->staff_limit !== null && $usage['staff_count'] > $plan->staff_limit) {
            return false;
        }

        if ($plan->customer_limit !== null && $usage['customer_count'] > $plan->customer_limit) {
            return false;
        }

        if ($plan->order_limit !== null && $usage['order_count'] > $plan->order_limit) {
            return false;
        }

        return true;
    }

    /**
     * Get compatibility issues for a plan.
     */
    protected function getCompatibilityIssues(SubscriptionPlan $plan, array $usage): array
    {
        $issues = [];

        if ($plan->staff_limit !== null && $usage['staff_count'] > $plan->staff_limit) {
            $excess = $usage['staff_count'] - $plan->staff_limit;
            $issues[] = [
                'type' => 'staff',
                'message' => "You currently have {$usage['staff_count']} staff members, but this plan allows only {$plan->staff_limit}.",
                'warning' => "You won't be able to add new staff until you're within the limit ({$excess} over limit).",
                'current' => $usage['staff_count'],
                'limit' => $plan->staff_limit,
                'excess' => $excess,
            ];
        }

        if ($plan->customer_limit !== null && $usage['customer_count'] > $plan->customer_limit) {
            $excess = $usage['customer_count'] - $plan->customer_limit;
            $issues[] = [
                'type' => 'customers',
                'message' => "You currently have {$usage['customer_count']} customers, but this plan allows only {$plan->customer_limit}.",
                'warning' => "You won't be able to add new customers until you're within the limit ({$excess} over limit).",
                'current' => $usage['customer_count'],
                'limit' => $plan->customer_limit,
                'excess' => $excess,
            ];
        }

        if ($plan->order_limit !== null && $usage['order_count'] > $plan->order_limit) {
            $excess = $usage['order_count'] - $plan->order_limit;
            $issues[] = [
                'type' => 'orders',
                'message' => "You have {$usage['order_count']} orders this month, but this plan allows only {$plan->order_limit} per month.",
                'warning' => "You won't be able to create new orders this month ({$excess} over limit). Limit resets next month.",
                'current' => $usage['order_count'],
                'limit' => $plan->order_limit,
                'excess' => $excess,
            ];
        }

        return $issues;
    }
}
