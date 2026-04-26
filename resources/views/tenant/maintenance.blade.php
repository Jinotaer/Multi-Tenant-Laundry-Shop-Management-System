<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="refresh" content="30">

        <title>System Under Maintenance</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif; background-color: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
            .card { background: #fff; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.06); max-width: 32rem; width: 100%; padding: 2.5rem 2rem; text-align: center; }
            .icon-wrap { width: 5rem; height: 5rem; border-radius: 9999px; background: linear-gradient(135deg, #fef3c7, #fde68a); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; }
            .icon-wrap svg { width: 2.5rem; height: 2.5rem; color: #d97706; }
            .badge { display: inline-block; background-color: #fef9c3; color: #854d0e; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; padding: 0.25rem 0.75rem; border-radius: 9999px; border: 1px solid #fde047; margin-bottom: 1rem; }
            h1 { font-size: 1.6rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; letter-spacing: -0.01em; }
            .shop-name { font-size: 0.9rem; color: #6b7280; margin-bottom: 1rem; }
            .shop-name strong { color: #374151; }
            .message { color: #6b7280; font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.75rem; }
            .divider { border: none; border-top: 1px solid #f3f4f6; margin-bottom: 1.5rem; }
            .status-row { display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.85rem; color: #6b7280; margin-bottom: 1.5rem; }
            .pulse { width: 0.6rem; height: 0.6rem; border-radius: 9999px; background-color: #f59e0b; animation: pulse 1.5s infinite; flex-shrink: 0; }
            @keyframes pulse {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.4; transform: scale(0.85); }
            }
            .btn-refresh { display: inline-block; padding: 0.625rem 1.5rem; background-color: #1f2937; color: #fff; font-size: 0.85rem; font-weight: 600; border-radius: 0.5rem; text-decoration: none; border: none; cursor: pointer; transition: background-color 0.15s; }
            .btn-refresh:hover { background-color: #374151; }
            .footer-note { font-size: 0.78rem; color: #9ca3af; margin-top: 1.25rem; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon-wrap">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                </svg>
            </div>

            <span class="badge">Maintenance In Progress</span>

            <h1>System Under Maintenance</h1>

            <p class="shop-name">
                <strong>{{ tenant()->data['shop_name'] ?? tenant()->id }}</strong>
            </p>

            <p class="message">
                We're currently applying updates to improve your experience.
                The system will be back online shortly. Thank you for your patience.
            </p>

            <hr class="divider">

            <div class="status-row">
                <span class="pulse"></span>
                Update in progress &mdash; this page refreshes automatically every 30 seconds.
            </div>

            <button class="btn-refresh" onclick="window.location.reload()">Try Again</button>

            <p class="footer-note">
                If this takes longer than expected, please contact your shop administrator.
            </p>
        </div>
    </body>
</html>
