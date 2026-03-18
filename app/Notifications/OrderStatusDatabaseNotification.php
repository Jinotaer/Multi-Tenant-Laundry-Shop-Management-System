<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderStatusDatabaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Order $order,
        public string $newStatus,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'order_update',
            'title' => $this->newStatus === 'ready'
                ? "Order {$this->order->order_number} is ready for pickup"
                : "Order {$this->order->order_number} status updated",
            'body' => "Your order is now {$this->order->status_label}.",
            'status' => $this->order->status_label,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'url' => $this->orderUrl(),
        ];
    }

    /**
     * Get the stored notification type identifier.
     */
    public function databaseType(object $notifiable): string
    {
        return 'order-status';
    }

    /**
     * Get the destination URL for this notification.
     */
    private function orderUrl(): string
    {
        $domain = tenant()?->domains()->first()?->domain;
        $path = tenant()->hasFeature('customer_portal')
            ? "/portal/{$this->order->id}"
            : '/notifications';

        if ($domain) {
            return "http://{$domain}{$path}";
        }

        return route('tenant.notifications.index', absolute: false);
    }
}
