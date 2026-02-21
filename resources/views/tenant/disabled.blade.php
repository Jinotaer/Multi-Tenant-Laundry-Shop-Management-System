<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Shop Unavailable</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif; background-color: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
            .card { background: #fff; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.1), 0 1px 2px rgba(0,0,0,.06); max-width: 28rem; width: 100%; padding: 2.5rem 2rem; text-align: center; }
            .icon-wrap { width: 4rem; height: 4rem; border-radius: 9999px; background-color: #fee2e2; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; }
            .icon-wrap svg { width: 2rem; height: 2rem; color: #dc2626; }
            h1 { font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; }
            p { color: #6b7280; font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem; }
            .btn { display: inline-block; padding: 0.625rem 1.25rem; background-color: #1f2937; color: #fff; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; border-radius: 0.375rem; text-decoration: none; transition: background-color 0.15s; }
            .btn:hover { background-color: #374151; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon-wrap">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>

            <h1>Shop Unavailable</h1>
            <p>This shop has been temporarily disabled by the administrator. Please contact support for more information.</p>

            <a href="mailto:support@laundrytrack.com" class="btn">Contact Support</a>
        </div>
    </body>
</html>
