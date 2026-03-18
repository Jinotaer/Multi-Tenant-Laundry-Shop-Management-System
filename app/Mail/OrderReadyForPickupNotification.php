<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReadyForPickupNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Order $order) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your order #{$this->order->order_number} is ready for pickup!",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.order-ready-for-pickup-notification',
            with: [
                'orderNumber' => $this->order->order_number,
                'customerName' => $this->order->customer->name,
                'serviceName' => $this->order->service?->name ?? 'Custom',
                'totalAmount' => number_format($this->order->total_amount, 2),
                'isPaid' => $this->order->payment_status === 'paid',
                'actionUrl' => $this->actionUrl(),
                'actionLabel' => tenant()->hasFeature('customer_portal') ? 'View Order Details' : 'View Notifications',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get the customer-facing action URL for this notification.
     */
    private function actionUrl(): string
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
