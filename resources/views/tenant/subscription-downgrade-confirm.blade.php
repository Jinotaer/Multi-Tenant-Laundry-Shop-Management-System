<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Confirm {{ $newPlan->price > $currentPlan->price ? 'Upgrade' : 'Plan Change' }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl w-full space-y-6">
                
                @php
                    $isUpgrade = $newPlan->price > $currentPlan->price;
                    $actionText = $isUpgrade ? 'Upgrade' : 'Downgrade';
                    $actionColor = $isUpgrade ? 'from-green-500 to-emerald-500' : 'from-amber-500 to-orange-500';
                @endphp

                {{-- Warning Banner --}}
                <div class="bg-gradient-to-r {{ $actionColor }} rounded-lg shadow-sm p-6 text-white">
                    <div class="flex items-start gap-4">
                        <svg class="h-8 w-8 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            @if($isUpgrade)
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            @endif
                        </svg>
                        <div>
                            <h3 class="text-lg font-bold mb-1">{{ $isUpgrade ? '🚀' : '⚠️' }} Confirm {{ $actionText }}</h3>
                            <p class="text-sm opacity-90">
                                @if($isUpgrade)
                                    You are about to upgrade your subscription. Review the new features and benefits below.
                                @else
                                    You are about to downgrade your subscription. Please review the changes carefully.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Plan Comparison --}}
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Plan Comparison</h3>
                        
                        <div class="grid grid-cols-2 gap-6">
                            {{-- Current Plan --}}
                            <div>
                                <p class="text-sm text-gray-500 mb-2">Current Plan</p>
                                <p class="text-2xl font-bold text-gray-900 mb-1">{{ $currentPlan->name }}</p>
                                <p class="text-xl font-semibold text-gray-600">{{ $currentPlan->formatted_price }}</p>
                            </div>

                            {{-- New Plan --}}
                            <div>
                                <p class="text-sm text-gray-500 mb-2">New Plan</p>
                                <p class="text-2xl font-bold text-indigo-600 mb-1">{{ $newPlan->name }}</p>
                                <p class="text-xl font-semibold text-indigo-600">{{ $newPlan->formatted_price }}</p>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="pb-3">Feature</th>
                                        <th class="pb-3 text-center">Current</th>
                                        <th class="pb-3 text-center">New</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr>
                                        <td class="py-3 font-medium text-gray-900">Staff Limit</td>
                                        <td class="py-3 text-center text-gray-900">{{ $currentPlan->staff_limit_display }}</td>
                                        <td class="py-3 text-center {{ ($newPlan->staff_limit !== null && $currentPlan->staff_limit !== null && $newPlan->staff_limit < $currentPlan->staff_limit) ? 'text-red-600 font-semibold' : (($newPlan->staff_limit === null || ($currentPlan->staff_limit !== null && $newPlan->staff_limit > $currentPlan->staff_limit)) ? 'text-green-600 font-semibold' : 'text-gray-900') }}">
                                            {{ $newPlan->staff_limit_display }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-3 font-medium text-gray-900">Customer Limit</td>
                                        <td class="py-3 text-center text-gray-900">{{ $currentPlan->customer_limit_display }}</td>
                                        <td class="py-3 text-center {{ ($newPlan->customer_limit !== null && $currentPlan->customer_limit !== null && $newPlan->customer_limit < $currentPlan->customer_limit) ? 'text-red-600 font-semibold' : (($newPlan->customer_limit === null || ($currentPlan->customer_limit !== null && $newPlan->customer_limit > $currentPlan->customer_limit)) ? 'text-green-600 font-semibold' : 'text-gray-900') }}">
                                            {{ $newPlan->customer_limit_display }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-3 font-medium text-gray-900">Monthly Orders</td>
                                        <td class="py-3 text-center text-gray-900">{{ $currentPlan->order_limit_display }}</td>
                                        <td class="py-3 text-center {{ ($newPlan->order_limit !== null && $currentPlan->order_limit !== null && $newPlan->order_limit < $currentPlan->order_limit) ? 'text-red-600 font-semibold' : (($newPlan->order_limit === null || ($currentPlan->order_limit !== null && $newPlan->order_limit > $currentPlan->order_limit)) ? 'text-green-600 font-semibold' : 'text-gray-900') }}">
                                            {{ $newPlan->order_limit_display }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @if($isUpgrade)
                    {{-- Features You'll Gain --}}
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-green-900 mb-4">✨ New Features You'll Get</h3>
                        <ul class="space-y-2">
                            @if($newPlan->hasFeature('analytics_dashboard') && !$currentPlan->hasFeature('analytics_dashboard'))
                                <li class="flex items-center gap-2 text-green-800">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    Analytics Dashboard
                                </li>
                            @endif
                            @if($newPlan->hasFeature('priority_support') && !$currentPlan->hasFeature('priority_support'))
                                <li class="flex items-center gap-2 text-green-800">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    Priority Support
                                </li>
                            @endif
                            @if($newPlan->hasFeature('custom_branding') && !$currentPlan->hasFeature('custom_branding'))
                                <li class="flex items-center gap-2 text-green-800">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    Custom Branding
                                </li>
                            @endif
                            @if($newPlan->hasFeature('expense_tracking') && !$currentPlan->hasFeature('expense_tracking'))
                                <li class="flex items-center gap-2 text-green-800">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    Expense Tracking
                                </li>
                            @endif
                            <li class="flex items-center gap-2 text-green-800">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Higher limits on staff, customers, and orders
                            </li>
                        </ul>
                    </div>
                @else
                    {{-- Features You'll Lose --}}
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-red-900 mb-4">⚠️ Features You'll Lose</h3>
                        <ul class="space-y-2">
                            @if(!$newPlan->hasFeature('analytics_dashboard') && $currentPlan->hasFeature('analytics_dashboard'))
                                <li class="flex items-center gap-2 text-red-800">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Analytics Dashboard
                                </li>
                            @endif
                            @if(!$newPlan->hasFeature('priority_support') && $currentPlan->hasFeature('priority_support'))
                                <li class="flex items-center gap-2 text-red-800">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Priority Support
                                </li>
                            @endif
                            @if(!$newPlan->hasFeature('custom_branding') && $currentPlan->hasFeature('custom_branding'))
                                <li class="flex items-center gap-2 text-red-800">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Custom Branding
                                </li>
                            @endif
                            @if(!$newPlan->hasFeature('expense_tracking') && $currentPlan->hasFeature('expense_tracking'))
                                <li class="flex items-center gap-2 text-red-800">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Expense Tracking
                                </li>
                            @endif
                            <li class="flex items-center gap-2 text-red-800">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Lower limits on staff, customers, and orders
                            </li>
                        </ul>
                    </div>
                @endif

                {{-- Confirmation Form --}}
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirm {{ $actionText }}</h3>
                    
                    <form method="POST" action="{{ route('tenant.subscription.change-plan.checkout') }}">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $newPlan->id }}">

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Type <span class="font-bold {{ $isUpgrade ? 'text-green-600' : 'text-red-600' }}">{{ strtoupper($actionText) }}</span> to confirm
                            </label>
                            <input 
                                type="text" 
                                name="confirmation" 
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Type {{ strtoupper($actionText) }}"
                            >
                            @error('confirmation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4">
                            <button 
                                type="submit" 
                                class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3 {{ $isUpgrade ? 'bg-green-600 hover:bg-green-700' : 'bg-indigo-600 hover:bg-indigo-700' }} text-white font-semibold rounded-lg transition">
                                Proceed to Payment
                            </button>
                            <a 
                                href="{{ route('tenant.subscription.change-plan') }}" 
                                class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>

                <p class="text-center text-sm text-gray-500">
                    Need help? Email <a href="mailto:support@laundrytrack.com" class="text-indigo-600 hover:text-indigo-700 font-medium">support@laundrytrack.com</a>
                </p>

            </div>
        </div>
    </body>
</html>
