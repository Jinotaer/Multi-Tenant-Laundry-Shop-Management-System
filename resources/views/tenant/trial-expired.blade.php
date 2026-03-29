<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Trial Expired</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif; background-color: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
            .card { background: #fff; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.06); max-width: 32rem; width: 100%; padding: 2.5rem 2rem; text-align: center; }
            .icon-wrap { width: 5rem; height: 5rem; border-radius: 9999px; background: linear-gradient(135deg, #fef3c7, #fde68a); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; }
            .icon-wrap svg { width: 2.5rem; height: 2.5rem; color: #d97706; }
            h1 { font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; }
            .subtitle { color: #6b7280; font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem; }
            .plan-info { background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem; }
            .plan-name { font-size: 1.1rem; font-weight: 600; color: #374151; }
            .plan-expired { font-size: 0.85rem; color: #dc2626; margin-top: 0.25rem; }
            .benefits { text-align: left; margin-bottom: 1.5rem; }
            .benefits h3 { font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
            .benefits ul { list-style: none; padding: 0; }
            .benefits li { display: flex; align-items: center; gap: 0.5rem; padding: 0.375rem 0; font-size: 0.875rem; color: #4b5563; }
            .benefits li svg { width: 1rem; height: 1rem; color: #10b981; flex-shrink: 0; }
            .btn-upgrade { display: inline-block; width: 100%; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; font-size: 0.9rem; font-weight: 600; border-radius: 0.5rem; text-decoration: none; transition: opacity 0.15s; border: none; cursor: pointer; margin-bottom: 0.75rem; }
            .btn-upgrade:hover { opacity: 0.9; }
            .btn-logout { display: inline-block; padding: 0.5rem 1rem; color: #6b7280; font-size: 0.8rem; text-decoration: none; transition: color 0.15s; background: none; border: none; cursor: pointer; }
            .btn-logout:hover { color: #374151; }
            .contact { font-size: 0.8rem; color: #9ca3af; margin-top: 1rem; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon-wrap">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h1>Your Free Trial Has Expired</h1>
            <p class="subtitle">Your 30-day free trial for <strong>{{ tenant()->data['shop_name'] ?? tenant()->id }}</strong> has ended. Upgrade to a paid plan to continue using your shop.</p>

            @if(tenant()->subscriptionPlan)
                <div class="plan-info">
                    <p class="plan-name">{{ tenant()->subscriptionPlan->name }} Plan</p>
                    <p class="plan-expired">Expired on {{ tenant()->trial_ends_at->format('M d, Y') }}</p>
                </div>
            @endif

            <div class="benefits">
                <h3>Upgrading unlocks</h3>
                <ul>
                    <li>
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Full access to your shop dashboard
                    </li>
                    <li>
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Manage staff, customers & orders
                    </li>
                    <li>
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Premium features & priority support
                    </li>
                    <li>
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        No interruptions to your business
                    </li>
                </ul>
            </div>

            <a href="{{ route('tenant.subscription.renew') }}" class="btn-upgrade">Renew Subscription Now</a>

            @auth
                <form method="POST" action="{{ route('tenant.logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn-logout">Sign out</button>
                </form>
            @endauth

            <p class="contact">Need help? Email <a href="mailto:support@laundrytrack.com" style="color: #6366f1;">support@laundrytrack.com</a></p>
        </div>
    </body>
</html>
