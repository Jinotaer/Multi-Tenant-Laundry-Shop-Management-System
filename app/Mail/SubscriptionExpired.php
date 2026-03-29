<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpired extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public int $graceDaysRemaining
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Subscription Has Expired - Renew Now',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-expired',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
