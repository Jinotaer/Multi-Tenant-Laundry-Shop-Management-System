<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Change Subscription Plan</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-5xl mx-auto">
                
                {{-- Header --}}
                <div class="text-center mb-12">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Change Subscription Plan</h1>
                    <p class="mt-3 text-base text-gray-500 max-w-2xl mx-auto">
                        Choose a plan that fits your needs. Your current usage: <strong class="text-gray-700">{{ $currentUsage['staff_count'] }} Staff</strong> • <strong class="text-gray-700">{{ $currentUsage['customer_count'] }} Customers</strong>
                    </p>
                </div>

                {{-- Current Plan Info --}}
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl shadow-sm p-6 mb-8 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90 mb-1">Current Plan</p>
                            <h3 class="text-2xl font-bold">{{ $currentPlan->name }}</h3>
                            <p class="text-sm opacity-90 mt-1">{{ $currentPlan->formatted_price }}</p>
                        </div>
                        <a href="{{ route('tenant.subscription.renew') }}" class="inline-flex items-center px-4 py-2 bg-white text-indigo-600 font-semibold rounded-lg hover:bg-gray-100 transition text-sm">
                            Keep This Plan
                        </a>
                    </div>
                </div>

                {{-- Available Plans --}}
                <div class="grid grid-cols-1 md:grid-cols-{{ count($availablePlans) > 2 ? '3' : count($availablePlans) }} gap-6 mb-8">
                    @forelse($availablePlans as $plan)
                        @php
                            $isPremium = !$plan->isFree();
                            $isUpgrade = $plan->price > $currentPlan->price;
                            $allFeatures = config('themes.features');
                            $planFeatures = $plan->features ?? [];
                        @endphp

                        <div class="relative bg-white rounded-2xl shadow-sm border-2 transition-all {{ $plan->is_compatible ? 'hover:shadow-lg' : 'opacity-60' }} {{ $isPremium ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-gray-300' }}">

                            {{-- Badge --}}
                            @if($isUpgrade && $plan->is_compatible)
                                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-green-600 text-white shadow-md">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
                                        </svg>
                                        Upgrade
                                    </span>
                                </div>
                            @elseif(!$plan->is_compatible)
                                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-red-600 text-white shadow-md">
                                        <svg class="inline-block h-4 w-4 align-text-bottom" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg> Incompatible
                                    </span>
                                </div>
                            @endif

                            <div class="p-6 sm:p-8">
                                {{-- Plan name & price --}}
                                <div class="text-center {{ ($isUpgrade && $plan->is_compatible) || !$plan->is_compatible ? 'pt-2' : '' }}">
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
                                @if($plan->is_compatible)
                                    <a href="{{ route('tenant.subscription.change-plan.confirm', $plan) }}"
                                       class="block w-full text-center px-4 py-3 rounded-xl text-sm font-bold transition {{ $isUpgrade ? 'bg-green-600 text-white hover:bg-green-700 shadow-md shadow-green-200' : 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-md shadow-indigo-200' }}">
                                        Select Plan
                                    </a>
                                @else
                                    <button disabled class="block w-full text-center px-4 py-3 rounded-xl text-sm font-bold bg-gray-300 text-gray-500 cursor-not-allowed">
                                        Unavailable
                                    </button>
                                @endif

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

                                {{-- Compatibility Issues --}}
                                @if(!$plan->is_compatible && count($plan->compatibility_issues) > 0)
                                    <div class="mt-6 pt-6 border-t border-gray-100">
                                        <p class="text-xs font-semibold text-red-600 uppercase tracking-wider mb-2"><svg class="inline-block h-4 w-4 align-text-bottom" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg> Why unavailable?</p>
                                        <ul class="space-y-1.5">
                                            @foreach($plan->compatibility_issues as $issue)
                                                <li class="text-xs text-red-600">• {{ $issue }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

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
                    @empty
                        <div class="col-span-full bg-white rounded-lg shadow-sm p-8 text-center">
                            <p class="text-gray-600">No other plans available.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Help Section --}}
                <div class="text-center text-sm text-gray-400">
                    <p>Need help choosing a plan? <a href="mailto:support@laundrytrack.com?subject=Plan%20Change%20Help&body=Hi%2C%0A%0AI%20need%20help%20choosing%20a%20subscription%20plan.%0A%0AShop%20Name%3A%20{{ urlencode($shopName) }}%0ATenant%20ID%3A%20{{ tenant()->id }}%0ACurrent%20Plan%3A%20{{ urlencode($currentPlan->name) }}%0A%0AQuestion%3A%20" class="text-indigo-500 hover:text-indigo-600 underline">Contact us</a></p>
                </div>

            </div>
        </div>
    </body>
</html>
