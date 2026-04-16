<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusSmsNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['sms'];
    }

    /**
     * Send SMS notification.
     */
    public function toSms(object $notifiable): bool
    {
        $smsService = app(SmsService::class);

        // Get customer phone number
        $phoneNumber = $this->order->customer->phone ?? null;

        if (!$phoneNumber) {
            return false;
        }

        return $smsService->sendOrderStatusNotification(
            $phoneNumber,
            $this->order->customer->name,
            $this->order->order_number,
            $this->newStatus
        );
    }
}
