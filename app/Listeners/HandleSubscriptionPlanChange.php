<?php

namespace App\Listeners;

use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleSubscriptionPlanChange
{
    /**
     * Handle the event when a tenant's subscription plan changes.
     */
    public function handle(object $event): void
    {
        if (!isset($event->tenant)) {
            return;
        }

        $tenant = $event->tenant;

        // Check if online_payments feature was removed
        if (!$tenant->hasFeature('online_payments')) {
            $this->handleOnlinePaymentsRemoval($tenant);
        }

        // Check if customer_loyalty feature was removed
        if (!$tenant->hasFeature('customer_loyalty')) {
            $this->handleCustomerLoyaltyRemoval($tenant);
        }
    }

    /**
     * Handle cleanup when online_payments feature is removed.
     */
    private function handleOnlinePaymentsRemoval(Tenant $tenant): void
    {
        // Cancel pending PayMongo payments
        Payment::where('tenant_id', $tenant->id)
            ->where('payment_type', 'order')
            ->where('status', 'pending')
            ->whereNotNull('paymongo_checkout_id')
            ->update([
                'status' => 'cancelled',
                'metadata' => \DB::raw("JSON_SET(COALESCE(metadata, '{}'), '$.cancellation_reason', 'Subscription downgraded')"),
            ]);

        // Note: We keep PayMongo credentials in case they upgrade again
        // Tenant can manually remove them from Payment Settings if they upgrade
    }

    /**
     * Handle cleanup when customer_loyalty feature is removed.
     */
    private function handleCustomerLoyaltyRemoval(Tenant $tenant): void
    {
        // Loyalty data is preserved in case they upgrade again
        // No cleanup needed
    }
}
