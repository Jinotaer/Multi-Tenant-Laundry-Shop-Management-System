<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Subscription & Usage') }}
        </h2>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="space-y-6">

        {{-- Trial Status Banner --}}
        @if($isOnTrial)
            <div class="bg-gradient-to-r {{ $trialDaysRemaining <= 7 ? 'from-amber-500 to-orange-500' : 'from-blue-500 to-indigo-600' }} rounded-lg shadow-sm p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-lg font-bold">Free Trial Active</h3>
                        </div>
                        <p class="text-sm opacity-90">
                            {{ $trialDaysRemaining }} {{ Str::plural('day', $trialDaysRemaining) }} remaining
                            &mdash; expires {{ $trialEndsAt->format('M d, Y') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-black">{{ $trialDaysRemaining }}</p>
                        <p class="text-xs uppercase tracking-wide opacity-80">days left</p>
                    </div>
                </div>
                @if($trialDaysRemaining <= 7)
                    <div class="mt-4 bg-white/20 rounded-lg p-3">
                        <p class="text-sm font-medium">⚠️ Your trial is ending soon. Contact your administrator to upgrade and avoid losing access.</p>
                    </div>
                @endif
            </div>
        @elseif($isPaid)
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg shadow-sm p-4 text-white">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-semibold">Active Subscription</p>
                </div>
            </div>
        @endif
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Current Plan</h3>
                    @if($plan)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $plan->isFree() ? 'bg-gray-100 text-gray-800' : $theme['badge_bg'] . ' ' . $theme['badge_text'] }}">
                            {{ $plan->name }}
                        </span>
                    @endif
                </div>

                @if($plan)
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">Price</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $plan->formatted_price }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Billing Cycle</p>
                            <p class="text-2xl font-bold text-gray-900 capitalize">{{ $plan->billing_cycle }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Plan Type</p>
                            <p class="text-2xl font-bold {{ $plan->isFree() ? 'text-green-600' : 'text-indigo-600' }}">
                                {{ $plan->isFree() ? 'Free' : 'Premium' }}
                            </p>
                        </div>
                    </div>

                    @if($plan->description)
                        <p class="mt-4 text-sm text-gray-500">{{ $plan->description }}</p>
                    @endif
                @else
                    <p class="text-sm text-gray-500">No plan assigned. Contact your administrator.</p>
                @endif
            </div>
        </div>

        {{-- Usage Overview --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Usage Overview</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Staff Usage --}}
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-700">Staff Accounts</p>
                            <p class="text-sm text-gray-500">
                                {{ $usage['staff']['current'] }} / {{ $usage['staff']['limit'] }}
                            </p>
                        </div>
                        @if($usage['staff']['limit'] !== 'Unlimited')
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full transition-all {{ $usage['staff']['percentage'] >= 90 ? 'bg-red-500' : ($usage['staff']['percentage'] >= 70 ? 'bg-yellow-500' : 'bg-indigo-500') }}" style="width: {{ $usage['staff']['percentage'] }}%"></div>
                            </div>
                            @if($usage['staff']['percentage'] >= 90)
                                <p class="text-xs text-red-500 mt-1">Nearing limit</p>
                            @endif
                        @else
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full bg-green-500" style="width: 5%"></div>
                            </div>
                            <p class="text-xs text-green-600 mt-1">Unlimited</p>
                        @endif
                    </div>

                    {{-- Customer Usage --}}
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-700">Customers</p>
                            <p class="text-sm text-gray-500">
                                {{ $usage['customers']['current'] }} / {{ $usage['customers']['limit'] }}
                            </p>
                        </div>
                        @if($usage['customers']['limit'] !== 'Unlimited')
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full transition-all {{ $usage['customers']['percentage'] >= 90 ? 'bg-red-500' : ($usage['customers']['percentage'] >= 70 ? 'bg-yellow-500' : 'bg-indigo-500') }}" style="width: {{ $usage['customers']['percentage'] }}%"></div>
                            </div>
                            @if($usage['customers']['percentage'] >= 90)
                                <p class="text-xs text-red-500 mt-1">Nearing limit</p>
                            @endif
                        @else
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full bg-green-500" style="width: 5%"></div>
                            </div>
                            <p class="text-xs text-green-600 mt-1">Unlimited</p>
                        @endif
                    </div>

                    {{-- Order Usage --}}
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-700">Orders (This Month)</p>
                            <p class="text-sm text-gray-500">
                                {{ $usage['orders']['current'] }} / {{ $usage['orders']['limit'] }}
                            </p>
                        </div>
                        @if($usage['orders']['limit'] !== 'Unlimited')
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full transition-all {{ $usage['orders']['percentage'] >= 90 ? 'bg-red-500' : ($usage['orders']['percentage'] >= 70 ? 'bg-yellow-500' : 'bg-indigo-500') }}" style="width: {{ $usage['orders']['percentage'] }}%"></div>
                            </div>
                            @if($usage['orders']['percentage'] >= 90)
                                <p class="text-xs text-red-500 mt-1">Nearing limit</p>
                            @endif
                        @else
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full bg-green-500" style="width: 5%"></div>
                            </div>
                            <p class="text-xs text-green-600 mt-1">Unlimited</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Features --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Features</h3>
                <p class="text-sm text-gray-500 mb-6">Features available on your current plan.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($allFeatures as $featureKey => $featureDef)
                        <div class="flex items-start gap-3 p-3 rounded-lg {{ in_array($featureKey, $tenantFeatures) ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200' }}">
                            @if(in_array($featureKey, $tenantFeatures))
                                <svg class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @else
                                <svg class="h-5 w-5 text-gray-300 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            @endif
                            <div>
                                <p class="text-sm font-medium {{ in_array($featureKey, $tenantFeatures) ? 'text-green-800' : 'text-gray-400' }}">
                                    {{ $featureDef['label'] }}
                                </p>
                                <p class="text-xs {{ in_array($featureKey, $tenantFeatures) ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $featureDef['description'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Upgrade Notice --}}
        @if($isOnTrial && !$isPaid)
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg shadow-sm p-6 text-white">
                <h3 class="text-lg font-bold mb-2">Upgrade Your Plan</h3>
                <p class="text-indigo-100 text-sm mb-4">Your free trial ends in <strong>{{ $trialDaysRemaining }} {{ Str::plural('day', $trialDaysRemaining) }}</strong>. Upgrade to continue using all features without interruption.</p>
                <p class="text-xs text-indigo-200">Contact your administrator to upgrade your plan.</p>
            </div>
        @elseif($plan && $plan->isFree() && !$isOnTrial)
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg shadow-sm p-6 text-white">
                <h3 class="text-lg font-bold mb-2">Upgrade to Premium</h3>
                <p class="text-indigo-100 text-sm mb-4">Get unlimited staff, customers, and orders. Plus access to all features including online payments, SMS notifications, and advanced reports.</p>
                <p class="text-xs text-indigo-200">Contact your administrator to upgrade your plan.</p>
            </div>
        @endif

    </div>
</x-tenant-layout>
