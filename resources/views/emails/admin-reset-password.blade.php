<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Reset Your Admin Password — LaundryTrack</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f3f4f6;
            color: #111827;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f3f4f6;
            padding: 48px 16px;
        }
        .container {
            max-width: 560px;
            margin: 0 auto;
        }

        /* Brand header above card */
        .brand {
            text-align: center;
            margin-bottom: 24px;
        }
        .brand-inner {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .brand-name {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.3px;
        }
        .brand-name span {
            color: #4f46e5;
        }

        /* Card */
        .card {
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 16px rgba(0,0,0,0.06);
        }

        /* Card top accent bar */
        .card-accent {
            height: 4px;
            background: linear-gradient(90deg, #4338ca 0%, #6366f1 50%, #818cf8 100%);
        }

        /* Card header */
        .card-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #3730a3 100%);
            padding: 40px 40px 36px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .card-header::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 160px;
            height: 160px;
            background: rgba(99, 102, 241, 0.2);
            border-radius: 50%;
        }
        .card-header::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 120px;
            height: 120px;
            background: rgba(139, 92, 246, 0.15);
            border-radius: 50%;
        }
        .header-icon {
            position: relative;
            z-index: 1;
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        .card-header h1 {
            position: relative;
            z-index: 1;
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.3px;
            margin-bottom: 6px;
        }
        .card-header p {
            position: relative;
            z-index: 1;
            font-size: 14px;
            color: #a5b4fc;
        }

        /* Card body */
        .card-body {
            padding: 36px 40px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
        }
        .message {
            font-size: 14px;
            color: #4b5563;
            line-height: 1.7;
            margin-bottom: 28px;
        }

        /* CTA Button */
        .btn-wrap {
            text-align: center;
            margin-bottom: 28px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: #ffffff !important;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            padding: 14px 36px;
            border-radius: 10px;
            letter-spacing: 0.1px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }

        /* Expiry notice */
        .notice {
            background-color: #fef9f0;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .notice-icon {
            flex-shrink: 0;
            margin-top: 1px;
        }
        .notice p {
            font-size: 13px;
            color: #92400e;
            line-height: 1.5;
        }
        .notice strong {
            color: #78350f;
        }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #f3f4f6;
            margin: 24px 0;
        }

        /* Security note */
        .security-note {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        /* Fallback URL */
        .fallback {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px 16px;
        }
        .fallback p {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 6px;
        }
        .fallback a {
            font-size: 12px;
            color: #4f46e5;
            word-break: break-all;
            text-decoration: none;
        }

        /* Card footer */
        .card-footer {
            background-color: #f9fafb;
            border-top: 1px solid #f3f4f6;
            padding: 20px 40px;
            text-align: center;
        }
        .card-footer p {
            font-size: 12px;
            color: #9ca3af;
        }
        .card-footer a {
            color: #6b7280;
            text-decoration: none;
        }

        /* Bottom caption */
        .caption {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">

            {{-- Brand --}}
            <div class="brand">
                <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                    <tr>
                        <td style="vertical-align: middle; padding-right: 10px;">
                            <div class="brand-icon">
                                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#ffffff">
                                    <rect x="5" y="3" width="14" height="18" rx="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14"/>
                          <circle cx="12" cy="14" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.5h.01M12 5.5h.01M15 5.5h.01"/>
                                </svg>
                            </div>
                        </td>
                        <td style="vertical-align: middle;">
                            <span class="brand-name">Laundry<span>Track</span></span>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Card --}}
            <div class="card">
                <div class="card-accent"></div>

                {{-- Header --}}
                <div class="card-header">
                    <div class="header-icon">
                        <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#ffffff">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <h1>Reset Your Password</h1>
                    <p>Admin Portal · LaundryTrack</p>
                </div>

                {{-- Body --}}
                <div class="card-body">
                    <p class="greeting">Hello, {{ $name }}!</p>
                    <p class="message">
                        We received a request to reset the password for your <strong>LaundryTrack Admin</strong> account.
                        Click the button below to choose a new password. If you did not make this request, you can safely ignore this email.
                    </p>

                    {{-- CTA --}}
                    <div class="btn-wrap">
                        <a href="{{ $url }}" class="btn">Reset My Password</a>
                    </div>

                    {{-- Expiry notice --}}
                    <div class="notice">
                        <div class="notice-icon">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#d97706">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p>This reset link will expire in <strong>{{ $expiry }} minutes</strong>. Request a new one if it expires.</p>
                    </div>

                    <hr class="divider">

                    <p class="security-note">
                        For your security, this link can only be used once and is tied to your admin account email address.
                        If you did not request a password reset, no further action is required — your account remains secure.
                    </p>

                    {{-- Fallback URL --}}
                    <div class="fallback">
                        <p>If the button above doesn't work, copy and paste this URL into your browser:</p>
                        <a href="{{ $url }}">{{ $url }}</a>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="card-footer">
                    <p>&copy; {{ date('Y') }} LaundryTrack &mdash; Admin Portal. All rights reserved.</p>
                    <p style="margin-top: 4px;">This is an automated message, please do not reply.</p>
                </div>
            </div>

            {{-- Bottom caption --}}
            <p class="caption">You are receiving this because a password reset was requested for your admin account.</p>

        </div>
    </div>
</body>
</html>
