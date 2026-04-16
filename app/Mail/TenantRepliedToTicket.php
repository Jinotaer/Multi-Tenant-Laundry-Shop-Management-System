<?php

namespace App\Mail;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantRepliedToTicket extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public SupportMessage $supportMessage
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Reply on Support Ticket #'.$this->ticket->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-replied-ticket',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
