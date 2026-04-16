<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
        .message-box { background: white; padding: 15px; border-left: 4px solid #4F46E5; margin: 15px 0; }
        .button { display: inline-block; background: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 15px 0; }
        .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">New Reply from {{ $ticket->tenant->data['shop_name'] ?? $ticket->tenant_id }}</h2>
        </div>
        <div class="content">
            <p><strong>Ticket #{{ $ticket->id }}:</strong> {{ $ticket->subject }}</p>
            <p><strong>Priority:</strong> {{ ucfirst($ticket->priority) }}</p>
            
            <div class="message-box">
                <p style="margin: 0; color: #6b7280; font-size: 12px;">{{ $supportMessage->created_at->format('M d, Y H:i') }}</p>
                <p style="margin: 10px 0 0 0;">{{ $supportMessage->message }}</p>
            </div>

            <a href="{{ config('app.url') }}/admin/support-tickets/{{ $ticket->id }}" class="button">View Ticket</a>

            <p style="color: #6b7280; font-size: 14px; margin-top: 20px;">
                This is an automated notification. Please do not reply to this email.
            </p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
