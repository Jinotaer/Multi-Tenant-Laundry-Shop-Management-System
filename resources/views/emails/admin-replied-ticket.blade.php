<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #10B981; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
        .message-box { background: white; padding: 15px; border-left: 4px solid #10B981; margin: 15px 0; }
        .button { display: inline-block; background: #10B981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 15px 0; }
        .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">Support Team Replied to Your Ticket</h2>
        </div>
        <div class="content">
            <p>Hello {{ $ticket->submitted_by_name }},</p>
            <p>Our support team has replied to your ticket:</p>
            
            <p><strong>Ticket #{{ $ticket->id }}:</strong> {{ $ticket->subject }}</p>
            
            <div class="message-box">
                <p style="margin: 0; color: #6b7280; font-size: 12px;">{{ $supportMessage->created_at->format('M d, Y H:i') }}</p>
                <p style="margin: 10px 0 0 0;">{{ $supportMessage->message }}</p>
            </div>

            <a href="{{ $ticket->tenant->domains->first()?->domain ? 'http://' . $ticket->tenant->domains->first()->domain . '/support/' . $ticket->id : '#' }}" class="button">View & Reply</a>

            <p style="color: #6b7280; font-size: 14px; margin-top: 20px;">
                If you have any questions, simply reply to this ticket through your dashboard.
            </p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
