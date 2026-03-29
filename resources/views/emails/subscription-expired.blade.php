<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expired</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #dc2626, #b91c1c); color: #fff; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px 20px; }
        .alert-box { background: #fee2e2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert-box strong { color: #dc2626; }
        .grace-box { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .grace-box strong { color: #1e40af; }
        .details { background: #f9fafb; padding: 15px; border-radius: 6px; margin: 20px 0; }
        .details p { margin: 8px 0; }
        .btn { display: inline-block; padding: 14px 28px; background: linear-gradient(135deg, #dc2626, #b91c1c); color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
        .btn:hover { opacity: 0.9; }
        .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Subscription Expired</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            
            <div class="alert-box">
                <strong>Your subscription for {{ $tenant->data['shop_name'] ?? $tenant->id }} has expired.</strong>
            </div>

            @if($graceDaysRemaining > 0)
                <div class="grace-box">
                    <strong>Grace Period Active:</strong> You have {{ $graceDaysRemaining }} {{ Str::plural('day', $graceDaysRemaining) }} remaining to renew your subscription before your account is suspended.
                </div>
            @else
                <p style="color: #dc2626; font-weight: bold;">Your grace period has ended. Please renew immediately to restore access to your account.</p>
            @endif

            <div class="details">
                <p><strong>Plan:</strong> {{ $tenant->subscriptionPlan->name }}</p>
                <p><strong>Price:</strong> {{ $tenant->subscriptionPlan->formatted_price }}</p>
                <p><strong>Expired on:</strong> {{ $tenant->subscription_expires_at->format('F d, Y') }}</p>
                @if($graceDaysRemaining > 0)
                    <p><strong>Grace period ends:</strong> {{ $tenant->graceEndsAt()->format('F d, Y') }}</p>
                @endif
            </div>

            <p>Renew your subscription now to continue using all features without interruption.</p>

            @php
                $domain = $tenant->domains->first()?->domain ?? "{$tenant->id}.localhost";
                $renewUrl = "http://{$domain}:8000/subscription/renew";
            @endphp

            <center>
                <a href="{{ $renewUrl }}" class="btn">Renew Subscription Now</a>
            </center>

            <p style="margin-top: 30px; font-size: 14px; color: #6b7280;">
                If you have any questions or need assistance, please contact our support team immediately.
            </p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} LaundryTrack. All rights reserved.</p>
            <p>This is an automated notification. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
