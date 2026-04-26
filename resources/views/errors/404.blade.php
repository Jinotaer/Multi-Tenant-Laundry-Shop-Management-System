<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Page Not Found</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif; background-color: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
            .card { background: #fff; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.06); max-width: 30rem; width: 100%; padding: 2.5rem 2rem; text-align: center; }
            .code { font-size: 4.5rem; font-weight: 800; color: #e5e7eb; line-height: 1; margin-bottom: 0.5rem; letter-spacing: -0.05em; }
            .icon-wrap { width: 4.5rem; height: 4.5rem; border-radius: 9999px; background: linear-gradient(135deg, #ede9fe, #ddd6fe); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; }
            .icon-wrap svg { width: 2.25rem; height: 2.25rem; color: #7c3aed; }
            h1 { font-size: 1.4rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; }
            p { color: #6b7280; font-size: 0.92rem; line-height: 1.6; margin-bottom: 1.75rem; }
            .actions { display: flex; flex-direction: column; gap: 0.625rem; }
            .btn-primary { display: block; padding: 0.7rem 1.5rem; background-color: #4f46e5; color: #fff; font-size: 0.875rem; font-weight: 600; border-radius: 0.5rem; text-decoration: none; transition: opacity 0.15s; }
            .btn-primary:hover { opacity: 0.88; }
            .btn-secondary { display: block; padding: 0.7rem 1.5rem; background-color: #f9fafb; color: #374151; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; text-decoration: none; border: 1px solid #e5e7eb; transition: background-color 0.15s; }
            .btn-secondary:hover { background-color: #f3f4f6; }
        </style>
    </head>
    <body>
        <div class="card">
            <p class="code">404</p>

            <div class="icon-wrap">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0012.016 15a4.486 4.486 0 00-3.198 1.318M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                </svg>
            </div>

            <h1>Page Not Found</h1>

            <p>
                @if(function_exists('tenant') && tenant())
                    Sorry, the page you're looking for doesn't exist on
                    <strong>{{ tenant()->data['shop_name'] ?? tenant()->id }}</strong>.
                @else
                    Sorry, the page you're looking for doesn't exist or has been moved.
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
        </div>
    </body>
</html>
