<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Payment Successful — {{ config('app.name', 'LaundryTrack') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="w-full max-w-md text-center">

                {{-- Success Icon --}}
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
                    <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Successful!</h1>
                <p class="text-gray-500 mb-8">Your payment has been confirmed. Your shop is now fully activated.</p>

                {{-- Payment Details --}}
                @if(isset($payment))
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8 text-left">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Payment Receipt</p>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Amount</span>
                                <span class="font-semibold text-gray-900">₱{{ number_format((float) $payment->amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Status</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                            </div>
                            @if($payment->payment_method)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Method</span>
                                    <span class="font-medium text-gray-900">{{ ucfirst($payment->payment_method) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Paid at</span>
                                <span class="font-medium text-gray-900">{{ $payment->paid_at?->format('M d, Y h:i A') ?? now()->format('M d, Y h:i A') }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Continue to Dashboard --}}
                <a href="{{ route('tenant.dashboard') }}" class="inline-flex items-center justify-center w-full py-3.5 px-6 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition shadow-md shadow-indigo-200">
                    Go to Dashboard
                    <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>

            </div>
        </div>
    </body>
</html>
