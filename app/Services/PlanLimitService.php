<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;

class PlanLimitService
{
    public function __construct(
        protected Tenant $tenant
    ) {}

    /**
     * Get the tenant's current subscription plan.
     */
    public function plan(): ?SubscriptionPlan
    {
        return $this->tenant->subscriptionPlan;
    }

    /**
     * Check if a specific feature is available on the tenant's plan.
     */
    public function hasFeature(string $feature): bool
    {
        return $this->tenant->hasFeature($feature);
    }

    /**
     * Check if the tenant can add more staff based on plan limits.
     */
    public function canAddStaff(int $currentCount): bool
    {
        $plan = $this->plan();

        if (! $plan) {
            return true;
        }

        // 0 means unlimited
        if ($plan->staff_limit === 0) {
            return true;
        }

        return $currentCount < $plan->staff_limit;
    }

    /**
     * Check if the tenant can add more customers based on plan limits.
     */
    public function canAddCustomer(int $currentCount): bool
    {
        $plan = $this->plan();

        if (! $plan) {
            return true;
        }

        // null means unlimited
        if ($plan->customer_limit === null) {
            return true;
        }

        return $currentCount < $plan->customer_limit;
    }

    /**
     * Check if the tenant can add more orders this month based on plan limits.
     */
    public function canAddOrder(int $currentMonthlyCount): bool
    {
        $plan = $this->plan();

        if (! $plan) {
            return true;
        }

        // null means unlimited
        if ($plan->order_limit === null) {
            return true;
        }

        return $currentMonthlyCount < $plan->order_limit;
    }

    /**
     * Get usage summary compared to plan limits.
     *
     * @return array{staff: array{current: int, limit: int|string}, customers: array{current: int, limit: int|string}, orders: array{current: int, limit: int|string}}
     */
    public function getUsageSummary(int $staffCount, int $customerCount, int $monthlyOrderCount): array
    {
        $plan = $this->plan();

        return [
            'staff' => [
                'current' => $staffCount,
                'limit' => $plan?->staff_limit === 0 ? 'Unlimited' : ($plan?->staff_limit ?? 'Unlimited'),
                'percentage' => $plan && $plan->staff_limit > 0
                    ? min(100, round(($staffCount / $plan->staff_limit) * 100))
                    : 0,
            ],
            'customers' => [
                'current' => $customerCount,
                'limit' => $plan?->customer_limit ?? 'Unlimited',
                'percentage' => $plan && $plan->customer_limit !== null
                    ? min(100, round(($customerCount / $plan->customer_limit) * 100))
                    : 0,
            ],
            'orders' => [
                'current' => $monthlyOrderCount,
                'limit' => $plan?->order_limit ?? 'Unlimited',
                'percentage' => $plan && $plan->order_limit !== null
                    ? min(100, round(($monthlyOrderCount / $plan->order_limit) * 100))
                    : 0,
            ],
        ];
    }

    /**
     * Get the remaining count for a specific limit.
     */
    public function remainingStaff(int $currentCount): ?int
    {
        $plan = $this->plan();

        if (! $plan || $plan->staff_limit === 0) {
            return null; // unlimited
        }

        return max(0, $plan->staff_limit - $currentCount);
    }

    /**
     * Get the remaining customer slots.
     */
    public function remainingCustomers(int $currentCount): ?int
    {
        $plan = $this->plan();

        if (! $plan || $plan->customer_limit === null) {
            return null; // unlimited
        }

        return max(0, $plan->customer_limit - $currentCount);
    }

    /**
     * Get the remaining order slots this month.
     */
    public function remainingOrders(int $currentMonthlyCount): ?int
    {
        $plan = $this->plan();

        if (! $plan || $plan->order_limit === null) {
            return null; // unlimited
        }

        return max(0, $plan->order_limit - $currentMonthlyCount);
    }
}
