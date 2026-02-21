<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4f46e5; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background-color: #f9fafb; padding: 24px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px; }
        .info-box { background-color: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; margin: 16px 0; }
        .info-label { font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; }
        .info-value { font-size: 16px; color: #111827; margin-top: 4px; }
        .btn { display: inline-block; background-color: #4f46e5; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 16px; }
        .footer { text-align: center; padding: 16px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 24px;">Your Shop Has Been Approved!</h1>
    </div>
    <div class="content">
        <p>Hello {{ $registration->owner_name }},</p>

        <p>Great news! Your shop <strong>{{ $registration->shop_name }}</strong> has been approved and is now ready to use.</p>

        <div class="info-box">
            <div style="margin-bottom: 12px;">
                <div class="info-label">Shop Name</div>
                <div class="info-value">{{ $registration->shop_name }}</div>
            </div>
            <div style="margin-bottom: 12px;">
                <div class="info-label">Your Portal URL</div>
                <div class="info-value">http://{{ $domain }}:8000</div>
            </div>
            <div>
                <div class="info-label">Login Email</div>
                <div class="info-value">{{ $registration->owner_email }}</div>
            </div>
        </div>

        <p>You can now log in to your shop portal using the email and password you registered with.</p>

        <a href="http://{{ $domain }}:8000" class="btn">Go to Your Shop Portal</a>

        <p style="margin-top: 24px; font-size: 14px; color: #6b7280;">If you have any questions, please don't hesitate to contact our support team.</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
