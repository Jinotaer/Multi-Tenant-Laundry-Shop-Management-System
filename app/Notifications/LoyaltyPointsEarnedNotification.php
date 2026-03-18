<?php

namespace App\Notifications;

use App\Models\CustomerLoyalty;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LoyaltyPointsEarnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Order $order,
        public CustomerLoyalty $loyalty,
        public int $pointsEarned,
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
            'category' => 'loyalty_reward',
            'title' => $this->pointsEarned > 0
                ? "You earned {$this->pointsEarned} loyalty point".($this->pointsEarned === 1 ? '' : 's')
                : 'Your loyalty stamp was updated',
            'body' => "You now have {$this->loyalty->points} points, {$this->loyalty->stamps} stamps, and {$this->loyalty->tier} tier status.",
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'points_earned' => $this->pointsEarned,
            'points_total' => $this->loyalty->points,
            'stamps_total' => $this->loyalty->stamps,
            'tier' => $this->loyalty->tier,
            'url' => $this->portalUrl(),
        ];
    }

    /**
     * Get the stored notification type identifier.
     */
    public function databaseType(object $notifiable): string
    {
        return 'loyalty-earned';
    }

    /**
     * Get the destination URL for this notification.
     */
    private function portalUrl(): string
    {
        $domain = tenant()?->domains()->first()?->domain;
        $path = tenant()->hasFeature('customer_portal') ? '/portal' : '/notifications';

        if ($domain) {
            return "http://{$domain}{$path}";
        }

        return route('tenant.notifications.index', absolute: false);
    }
}
