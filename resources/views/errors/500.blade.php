<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Server Error</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif; background-color: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
            .card { background: #fff; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.06); max-width: 30rem; width: 100%; padding: 2.5rem 2rem; text-align: center; }
            .code { font-size: 4.5rem; font-weight: 800; color: #e5e7eb; line-height: 1; margin-bottom: 0.5rem; letter-spacing: -0.05em; }
            .icon-wrap { width: 4.5rem; height: 4.5rem; border-radius: 9999px; background: linear-gradient(135deg, #fee2e2, #fecaca); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; }
            .icon-wrap svg { width: 2.25rem; height: 2.25rem; color: #dc2626; }
            h1 { font-size: 1.4rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; }
            p { color: #6b7280; font-size: 0.92rem; line-height: 1.6; margin-bottom: 1.75rem; }
            .actions { display: flex; flex-direction: column; gap: 0.625rem; }
            .btn-primary { display: block; padding: 0.7rem 1.5rem; background-color: #1f2937; color: #fff; font-size: 0.875rem; font-weight: 600; border-radius: 0.5rem; text-decoration: none; transition: background-color 0.15s; }
            .btn-primary:hover { background-color: #374151; }
            .btn-secondary { display: block; padding: 0.7rem 1.5rem; background-color: #f9fafb; color: #374151; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; text-decoration: none; border: 1px solid #e5e7eb; transition: background-color 0.15s; }
            .btn-secondary:hover { background-color: #f3f4f6; }
            .contact { font-size: 0.78rem; color: #9ca3af; margin-top: 1.25rem; }
        </style>
    </head>
    <body>
        <div class="card">
            <p class="code">500</p>

            <div class="icon-wrap">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>

            <h1>Something Went Wrong</h1>

            <p>
                We ran into an unexpected error. Our team has been notified.
                @if(function_exists('tenant') && tenant())
                    Please try again or contact support if the issue persists.
                @else
                    Please try again in a moment.
                @endif
            </p>

            <div class="actions">
                @if(function_exists('tenant') && tenant())
                    <a href="{{ route('tenant.dashboard') }}" class="btn-primary">Go to Dashboard</a>
                    <a href="javascript:history.back()" class="btn-secondary">Go Back</a>
                @else
                    <a href="{{ url('/') }}" class="btn-primary">Go to Homepage</a>
                    <a href="javascript:history.back()" class="btn-secondary">Go Back</a>
                @endif
            </div>

            <p class="contact">
                Need help? Contact <a href="mailto:support@laundrytrack.com" style="color:#6366f1;">support@laundrytrack.com</a>
            </p>
        </div>
    </body>
</html>
