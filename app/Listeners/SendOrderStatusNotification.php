<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Mail\OrderReadyForPickupNotification;
use App\Mail\OrderStatusChangedNotification;
use App\Models\CustomerLoyalty;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order->load('customer', 'service');
        $customer = $order->customer;

        // Only send notifications if tenant has feature enabled
        if (!tenant()->hasFeature('notifications')) {
            return;
        }

        // Send email notification
        Mail::send(new OrderStatusChangedNotification($order, $event->newStatus));

        // Special notification when order is ready for pickup
        if ($event->newStatus === 'ready') {
            Mail::send(new OrderReadyForPickupNotification($order));
        }

        // Award loyalty points when order is claimed (completed)
        if ($event->newStatus === 'claimed') {
            $this->awardLoyaltyPoints($customer, $order);
        }
    }

    /**
     * Award loyalty points for completed order.
     */
    private function awardLoyaltyPoints($customer, $order): void
    {
        // Only if tenant has loyalty feature
        if (!tenant()->hasFeature('customer_loyalty')) {
            return;
        }

        // Get or create loyalty record
        $loyalty = CustomerLoyalty::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'points' => 0,
                'stamps' => 0,
                'tier' => 'bronze',
                'lifetime_spent' => 0,
            ]
        );

        // Award 1 point per ₱100 spent, multiplied by tier multiplier
        $basePoints = (int) ($order->total_amount / 100);
        $points = (int) ($basePoints * $loyalty->getRewardMultiplier());

        $loyalty->addPoints($points, $order->total_amount);
    }
}
