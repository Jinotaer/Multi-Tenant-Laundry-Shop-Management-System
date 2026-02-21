<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #dc2626; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background-color: #f9fafb; padding: 24px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px; }
        .reason-box { background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 16px; margin: 16px 0; }
        .footer { text-align: center; padding: 16px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 24px;">Shop Registration Update</h1>
    </div>
    <div class="content">
        <p>Hello {{ $registration->owner_name }},</p>

        <p>We regret to inform you that your shop registration for <strong>{{ $registration->shop_name }}</strong> has not been approved at this time.</p>

        @if ($registration->rejection_reason)
            <div class="reason-box">
                <strong>Reason:</strong>
                <p style="margin: 8px 0 0 0;">{{ $registration->rejection_reason }}</p>
            </div>
        @endif

        <p>If you believe this was a mistake or would like to resubmit your application, please contact our support team for assistance.</p>

        <p style="margin-top: 24px; font-size: 14px; color: #6b7280;">Thank you for your interest in our platform.</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
