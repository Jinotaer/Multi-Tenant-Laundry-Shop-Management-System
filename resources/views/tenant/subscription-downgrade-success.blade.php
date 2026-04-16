<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Plan Change Successful</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl w-full">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 text-center">
                        {{-- Success Icon --}}
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
                            <svg class="h-12 w-12 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Plan Changed Successfully!</h1>
                        <p class="text-gray-600 mb-8">Your subscription has been successfully changed to the {{ $plan->name }} plan.</p>

                        {{-- Payment Details --}}
                        @if(isset($payment))
                            <div class="bg-gray-50 rounded-lg p-6 mb-8 text-left">
                                <h3 class="font-semibold text-gray-900 mb-4">Payment Details</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">New Plan</span>
                                        <span class="font-semibold text-gray-900">{{ $plan->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Amount Paid</span>
                                        <span class="font-semibold text-gray-900">₱{{ number_format((float) $payment->amount, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Payment Method</span>
                                        <span class="font-semibold text-gray-900 capitalize">{{ $payment->payment_method ?? 'Online Payment' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Payment Date</span>
                                        <span class="font-semibold text-gray-900">{{ $payment->paid_at?->format('F d, Y h:i A') ?? now()->format('F d, Y h:i A') }}</span>
                                    </div>
                                    @if($tenant->subscription_expires_at)
                                        <div class="flex justify-between pt-3 border-t border-gray-200">
                                            <span class="text-gray-600">Next Renewal Date</span>
                                            <span class="font-semibold text-green-600">{{ $tenant->subscription_expires_at->format('F d, Y') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Actions --}}
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="{{ route('tenant.dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                                Go to Dashboard
                            </a>
                        </div>

                        <p class="mt-8 text-sm text-gray-500">
                            A confirmation email has been sent to your registered email address.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
