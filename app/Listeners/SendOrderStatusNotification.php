<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Mail\OrderReadyForPickupNotification;
use App\Mail\OrderStatusChangedNotification;
use App\Models\Customer;
use App\Models\CustomerLoyalty;
use App\Models\Order;
use App\Notifications\LoyaltyPointsEarnedNotification;
use App\Notifications\OrderStatusDatabaseNotification;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusNotification
{
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
        $smsService = app(SmsService::class);

        if (! $customer || $event->oldStatus === $event->newStatus) {
            return;
        }

        if (tenant()->hasFeature('notifications')) {
            $this->sendCustomerNotifications($customer, $order, $event->newStatus, $smsService);
        }

        if ($event->newStatus === 'claimed') {
            $loyalty = $this->awardLoyaltyPoints($customer, $order);

            if ($loyalty && tenant()->hasFeature('notifications')) {
                $customer->notify(new LoyaltyPointsEarnedNotification(
                    $order,
                    $loyalty,
                    $order->loyalty_points_awarded,
                ));
            }
        }
    }

    /**
     * Send customer-facing email and in-app notifications.
     */
    private function sendCustomerNotifications(Customer $customer, Order $order, string $newStatus, SmsService $smsService): void
    {
        if ($newStatus === 'ready') {
            $this->sendReadyNotificationWithFallback($customer, $order, $smsService);

            return;
        }

        if ($customer->email) {
            Mail::to($customer->email)->send(new OrderStatusChangedNotification($order, $newStatus));
        }

        $customer->notify(new OrderStatusDatabaseNotification($order, $newStatus));
    }

    /**
     * Send the ready notification through all available customer channels.
     */
    private function sendReadyNotificationWithFallback(Customer $customer, Order $order, SmsService $smsService): void
    {
        if (tenant()->hasFeature('sms_notifications')) {
            $smsService->sendOrderStatusUpdate($customer, $order, $order->status_label);
        }

        if ($customer->email) {
            try {
                Mail::to($customer->email)->send(new OrderReadyForPickupNotification($order));
            } catch (\Throwable $exception) {
                Log::warning('Ready notification email fallback failed.', [
                    'customer_id' => $customer->id,
                    'order_id' => $order->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $customer->notify(new OrderStatusDatabaseNotification($order, 'ready'));
    }

    /**
     * Award loyalty points for a completed order.
     */
    private function awardLoyaltyPoints(Customer $customer, Order $order): ?CustomerLoyalty
    {
        if (! tenant()->hasFeature('customer_loyalty') || $order->loyalty_points_awarded_at) {
            return null;
        }

        $loyalty = CustomerLoyalty::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'points' => 0,
                'stamps' => 0,
                'tier' => 'bronze',
                'lifetime_spent' => 0,
            ]
        );

        $basePoints = (int) ($order->total_amount / 100);
        $points = (int) ($basePoints * $loyalty->getRewardMultiplier());

        $loyalty->addPoints($points, (float) $order->total_amount);

        $order->forceFill([
            'loyalty_points_awarded' => $points,
            'loyalty_points_awarded_at' => now(),
        ])->saveQuietly();

        return $loyalty->refresh();
    }
}
