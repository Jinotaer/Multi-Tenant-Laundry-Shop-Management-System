<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Complete Payment — {{ config('app.name', 'LaundryTrack') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="w-full max-w-lg">

                {{-- Header --}}
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 mb-4">
                        <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Complete Your Payment</h1>
                    <p class="mt-2 text-gray-500">{{ $shopName }}</p>
                </div>

                {{-- Plan Summary Card --}}
                @if($plan)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">{{ $plan->name }} Plan</h3>
                                    @if($plan->description)
                                        <p class="text-sm text-gray-500 mt-0.5">{{ $plan->description }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-extrabold text-gray-900">{{ $plan->formatted_price }}</p>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-4 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Staff accounts</span>
                                    <span class="font-medium text-gray-900">{{ $plan->staff_limit_display }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Customers</span>
                                    <span class="font-medium text-gray-900">{{ $plan->customer_limit_display }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Orders / month</span>
                                    <span class="font-medium text-gray-900">{{ $plan->order_limit_display }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Payment Total --}}
                        <div class="bg-gray-50 px-6 py-4 border-t border-gray-100">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-semibold text-gray-700">Total Due</span>
                                <span class="text-xl font-extrabold text-indigo-600">₱{{ number_format((float) $plan->price, 2) }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Payment Methods Info --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Accepted Payment Methods</p>
                    <div class="flex flex-wrap gap-3">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 bg-gray-50 px-3 py-1.5 rounded-lg">
                            <svg class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                            Credit/Debit Card
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 bg-gray-50 px-3 py-1.5 rounded-lg">
                            <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
                            GCash
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 bg-gray-50 px-3 py-1.5 rounded-lg">
                            <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
                            Maya
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 bg-gray-50 px-3 py-1.5 rounded-lg">
                            <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
                            GrabPay
                        </span>
                    </div>
                </div>

                {{-- Error Message --}}
                @if(session('error'))
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                        <div class="flex gap-2">
                            <svg class="h-5 w-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                {{-- CTA --}}
                <form method="POST" action="{{ route('tenant.payment.checkout') }}">
                    @csrf
                    <button type="submit" class="w-full py-3.5 px-6 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition shadow-md shadow-indigo-200">
                        Pay ₱{{ number_format((float) $plan->price, 2) }} with PayMongo
                    </button>
                </form>

                <p class="mt-4 text-center text-xs text-gray-400">
                    Secure payment powered by <strong>PayMongo</strong>. Your data is encrypted and safe.
                </p>

                {{-- Logout --}}
                @auth
                    <div class="mt-6 text-center">
                        <form method="POST" action="{{ route('tenant.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-gray-400 hover:text-gray-600 transition">
                                Sign out
                            </button>
                        </form>
                    </div>
                @endauth

            </div>
        </div>
    </body>
</html>
