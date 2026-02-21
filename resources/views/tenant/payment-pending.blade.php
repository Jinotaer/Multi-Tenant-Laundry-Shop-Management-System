<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Payment Pending — {{ config('app.name', 'LaundryTrack') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <meta http-equiv="refresh" content="15">
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="w-full max-w-md text-center">

                {{-- Pending Icon --}}
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-amber-100 mb-6">
                    <svg class="h-10 w-10 text-amber-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Processing</h1>
                <p class="text-gray-500 mb-8">
                    Your payment is still being verified. This page will refresh automatically.
                    If payment has completed, you'll be redirected to your dashboard shortly.
                </p>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
                    <div class="flex items-center justify-center gap-3">
                        <svg class="h-5 w-5 text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-600">Waiting for confirmation…</span>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <a href="{{ route('tenant.payment.show') }}" class="inline-flex items-center justify-center w-full py-3 px-6 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-xl border border-gray-200 transition">
                        Return to Payment Page
                    </a>

                    @auth
                        <form method="POST" action="{{ route('tenant.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="w-full text-sm text-gray-400 hover:text-gray-600 transition py-2">
                                Sign out
                            </button>
                        </form>
                    @endauth
                </div>

            </div>
        </div>
    </body>
</html>
