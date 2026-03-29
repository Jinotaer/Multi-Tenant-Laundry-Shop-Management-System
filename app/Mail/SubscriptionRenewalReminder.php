<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionRenewalReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public int $daysRemaining
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Subscription Renewal Reminder - {$this->daysRemaining} Days Left",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-renewal-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
