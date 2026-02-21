<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Pricing — {{ config('app.name', 'LaundryTrack') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">

        {{-- Navbar --}}
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold text-indigo-600">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                    </svg>
                    LaundryTrack
                </a>
                <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-gray-700 transition">
                    &larr; Back to home
                </a>
            </div>
        </nav>

        {{-- Hero --}}
        <div class="pt-12 pb-8 text-center px-4">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">
                Simple, transparent pricing
            </h1>
            <p class="mt-3 text-base text-gray-500 max-w-lg mx-auto">
                Start with a <strong class="text-gray-700">30-day free trial</strong>. No credit card required. Upgrade anytime.
            </p>
        </div>

        {{-- Plan Cards --}}
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            <div class="grid grid-cols-1 md:grid-cols-{{ count($plans) }} gap-6 items-start">
                @foreach($plans as $plan)
                    @php
                        $isPremium = !$plan->isFree();
                        $allFeatures = config('themes.features');
                        $planFeatures = $plan->features ?? [];
                    @endphp

                    <div class="relative bg-white rounded-2xl shadow-sm border-2 transition-all hover:shadow-lg {{ $isPremium ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-gray-300' }}">

                        {{-- Popular badge --}}
                        @if($isPremium)
                            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-indigo-600 text-white shadow-md">
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" /></svg>
                                    Most Popular
                                </span>
                            </div>
                        @endif

                        <div class="p-6 sm:p-8">
                            {{-- Plan name & price --}}
                            <div class="text-center {{ $isPremium ? 'pt-2' : '' }}">
                                <h3 class="text-lg font-bold text-gray-900">{{ $plan->name }}</h3>
                                @if($plan->description)
                                    <p class="mt-1 text-sm text-gray-500">{{ $plan->description }}</p>
                                @endif
                                <div class="mt-4 mb-6">
                                    @if($plan->isFree())
                                        <span class="text-4xl font-extrabold text-gray-900">Free</span>
                                    @else
                                        <span class="text-4xl font-extrabold text-gray-900">₱{{ number_format((float) $plan->price, 0) }}</span>
                                        <span class="text-base font-medium text-gray-500">/{{ $plan->billing_cycle }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- CTA --}}
                            <a href="{{ route('shop.register', ['plan' => $plan->id]) }}"
                               class="block w-full text-center px-4 py-3 rounded-xl text-sm font-bold transition {{ $isPremium ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-md shadow-indigo-200' : 'bg-gray-900 text-white hover:bg-gray-800' }}">
                                Get started free
                            </a>

                            {{-- Limits --}}
                            <div class="mt-6 pt-6 border-t border-gray-100">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Plan Limits</p>
                                <div class="space-y-2.5">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Staff accounts</span>
                                        <span class="font-semibold text-gray-900">{{ $plan->staff_limit_display }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Customers</span>
                                        <span class="font-semibold text-gray-900">{{ $plan->customer_limit_display }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Orders / month</span>
                                        <span class="font-semibold text-gray-900">{{ $plan->order_limit_display }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Features --}}
                            <div class="mt-6 pt-6 border-t border-gray-100">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Features</p>
                                <ul class="space-y-2.5">
                                    @foreach($allFeatures as $featureKey => $featureConfig)
                                        <li class="flex items-center gap-2.5 text-sm {{ in_array($featureKey, $planFeatures) ? 'text-gray-700' : 'text-gray-300' }}">
                                            @if(in_array($featureKey, $planFeatures))
                                                <svg class="h-5 w-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                            @else
                                                <svg class="h-5 w-5 text-gray-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            @endif
                                            {{ $featureConfig['label'] }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- FAQ / Trust --}}
            <div class="mt-12 text-center text-sm text-gray-400">
                <p>All plans include a 30-day free trial. Cancel anytime during the trial at no cost.</p>
                <p class="mt-1">Questions? <a href="mailto:support@laundrytrack.com" class="text-indigo-500 hover:text-indigo-600 underline">Contact us</a></p>
            </div>
        </div>
    </body>
</html>
