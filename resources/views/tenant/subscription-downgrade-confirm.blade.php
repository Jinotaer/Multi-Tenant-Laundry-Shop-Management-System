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
                            <h3 class="text-lg font-bold mb-1">{{ $isUpgrade ? '<svg class="inline-block h-4 w-4 align-text-bottom" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/></svg>' : '<svg class="inline-block h-4 w-4 align-text-bottom" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>' }} Confirm {{ $actionText }}</h3>
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

                @if(!$isUpgrade && !empty($compatibilityIssues))
                    {{-- Usage Warnings --}}
                    <div class="bg-amber-50 border border-amber-300 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-amber-900 mb-4"><svg class="inline-block h-4 w-4 align-text-bottom" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg> Current Usage Exceeds New Plan Limits</h3>
                        <p class="text-sm text-amber-800 mb-4">Your existing data will be preserved, but you'll have restrictions until you're within the new limits:</p>
                        <ul class="space-y-3">
                            @foreach($compatibilityIssues as $issue)
                                <li class="bg-white rounded-lg p-4 border border-amber-300">
                                    <p class="font-semibold text-amber-900 mb-1">{{ $issue['message'] }}</p>
                                    <p class="text-sm text-amber-700">{{ $issue['warning'] }}</p>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-4 p-4 bg-amber-100 rounded-lg">
                            <p class="text-sm font-semibold text-amber-900 mb-2">What this means:</p>
                            <ul class="text-sm text-amber-800 space-y-1 list-disc list-inside">
                                <li>All your existing data remains safe and accessible</li>
                                <li>You cannot add new records beyond the plan limits</li>
                                <li>You can delete records to get back within limits</li>
                                <li>Monthly order limits reset at the start of each month</li>
                            </ul>
                        </div>
                        <div class="mt-4">
                            <label class="flex items-start gap-3">
                                <input type="checkbox" name="acknowledge_limits" value="1" required class="mt-1 h-4 w-4 border-gray-300 rounded">
                                <span class="text-sm text-amber-900">I understand that I won't be able to add new staff, customers, or orders beyond the plan limits until I reduce my current usage.</span>
                            </label>
                        </div>
                    </div>
                @endif

                @if($isUpgrade)
                    {{-- Features You'll Gain --}}
                    <div class="bg-green-50 border border-green-300 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-green-900 mb-4"><svg class="inline-block h-4 w-4 align-text-bottom" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/></svg> New Features You'll Get</h3>
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
                    <div class="bg-red-50 border border-red-300 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-red-900 mb-4"><svg class="inline-block h-4 w-4 align-text-bottom" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg> Features You'll Lose</h3>
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2"
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
                    Need help? Email <a href="mailto:support@laundrytrack.com?subject=Subscription%20Plan%20Change&body=Hi%2C%0A%0AI%20need%20help%20with%20my%20subscription%20plan%20change.%0A%0AShop%20Name%3A%20{{ urlencode($shopName) }}%0ATenant%20ID%3A%20{{ tenant()->id }}%0ACurrent%20Plan%3A%20{{ urlencode($currentPlan->name) }}%0ANew%20Plan%3A%20{{ urlencode($newPlan->name) }}%0A%0AQuestion%3A%20" class="text-indigo-600 hover:text-indigo-700 font-medium">support@laundrytrack.com</a>
                </p>

            </div>
        </div>
    </body>
</html>
