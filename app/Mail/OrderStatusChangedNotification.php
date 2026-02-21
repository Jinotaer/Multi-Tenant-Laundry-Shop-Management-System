<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangedNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order,
        public string $newStatus,
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $shopName = tenant('data')['shop_name'] ?? tenant('id');

        return new Envelope(
            subject: "Order #{$this->order->order_number} status updated - {$this->order->status_label}",
            from: env('MAIL_FROM_ADDRESS', 'noreply@laundrytrack.com'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.order-status-changed-notification',
            with: [
                'orderNumber' => $this->order->order_number,
                'newStatus' => $this->order->status_label,
                'customerName' => $this->order->customer->name,
                'serviceName' => $this->order->service?->name ?? 'Custom',
                'dueDate' => $this->order->due_date?->format('M d, Y'),
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
}
