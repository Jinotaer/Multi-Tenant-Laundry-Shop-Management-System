<x-tenant-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Subscription & Usage') }}
            </h2>
        </div>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="space-y-6">

        {{-- Grace Period Warning --}}
        @if($tenant->isInGracePeriod())
            <div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-lg shadow-sm p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <h3 class="text-lg font-bold">Grace Period Active</h3>
                        </div>
                        <p class="text-sm opacity-90">
                            Your subscription expired on {{ $tenant->subscription_expires_at->format('M d, Y') }}.
                            You have {{ $tenant->graceDaysRemaining() }} {{ Str::plural('day', $tenant->graceDaysRemaining()) }} remaining to renew.
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-black">{{ $tenant->graceDaysRemaining() }}</p>
                        <p class="text-xs uppercase tracking-wide opacity-80">days left</p>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('tenant.subscription.renew') }}" class="inline-block bg-white text-orange-600 px-6 py-2 rounded-lg font-bold hover:bg-gray-100 transition">
                        Renew Subscription Now
                    </a>
                </div>
            </div>
        @endif

        {{-- Trial Status Banner --}}
        @if($isOnTrial)
            <div
                class="bg-gradient-to-r {{ $trialDaysRemaining <= 7 ? 'from-amber-500 to-orange-500' : 'from-blue-500 to-indigo-600' }} rounded-lg shadow-sm p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
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
                        <p class="text-sm font-medium">⚠️ Your trial is ending soon. Contact your administrator to upgrade and
                            avoid losing access.</p>
                    </div>
                @endif
            </div>
        @elseif($isPaid)
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg shadow-sm p-6 text-white">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="font-semibold">Active Subscription</p>
                        </div>
                        @if($subscriptionRenewsAt)
                            <p class="text-sm opacity-90">
                                {{ $paidDaysRemaining }} {{ Str::plural('day', $paidDaysRemaining) }} until renewal
                                &mdash; renews {{ $subscriptionRenewsAt->format('M d, Y') }}
                            </p>
                        @else
                            <p class="text-sm opacity-90">Renewal date is not available for this subscription yet.</p>
                        @endif
                    </div>
                    @if($subscriptionRenewsAt)
                        <div class="text-right">
                            <p class="text-3xl font-black">{{ $paidDaysRemaining }}</p>
                            <p class="text-xs uppercase tracking-wide opacity-80">days left</p>
                        </div>
                    @endif
                </div>
                @if($paidDaysRemaining <= 7 && $paidDaysRemaining > 0)
                    <div class="mt-4 bg-white/20 rounded-lg p-3">
                        <p class="text-sm font-medium">⚠️ Your subscription is expiring soon. Renew now to avoid interruption.</p>
                        <a href="{{ route('tenant.subscription.renew') }}" class="inline-block mt-2 bg-white text-green-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-100 transition">
                            Renew Early
                        </a>
                    </div>
                @endif
            </div>
        @endif

        {{-- Current Plan and View All Plans Button --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Current Plan (Left - 2 columns) --}}
            <div class="lg:col-span-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg h-full">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Current Plan</h3>
                            @if($plan)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $plan->isFree() ? 'bg-gray-100 text-gray-800' : $theme['badge_bg'] . ' ' . $theme['badge_text'] }}">
                                    {{ $plan->name }}
                                </span>
                            @endif
                        </div>

                        @if($plan)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
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
                                @if($subscriptionRenewsAt)
                                    <div>
                                        <p class="text-sm text-gray-500">Next Renewal</p>
                                        <p class="text-2xl font-bold text-gray-900">{{ $subscriptionRenewsAt->format('M d, Y') }}</p>
                                        <p class="text-sm text-gray-500">{{ $paidDaysRemaining }}
                                            {{ Str::plural('day', $paidDaysRemaining) }} left</p>
                                    </div>
                                @endif
                            </div>

                            @if($plan->description)
                                <p class="mt-4 text-sm text-gray-500">{{ $plan->description }}</p>
                            @endif
                        @else
                            <p class="text-sm text-gray-500">No plan assigned. Contact your administrator.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- View All Plans Button (Right - 1 column) --}}
            <div class="lg:col-span-1">
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg shadow-sm p-6 text-white h-full flex flex-col justify-center">
                    <div class="text-center">
                        <svg class="h-12 w-12 mx-auto mb-4 opacity-90" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                        <h3 class="text-lg font-bold mb-2">Explore Plans</h3>
                        <p class="text-sm opacity-90 mb-4">Compare features and find the perfect plan for your business</p>
                        <a href="{{ route('tenant.subscription.plans') }}"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-white text-indigo-600 font-semibold rounded-lg hover:bg-gray-100 transition shadow-lg">
                            <span>View All Plans</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    </div>
                </div>
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
                                <div class="h-2.5 rounded-full transition-all {{ $usage['staff']['percentage'] >= 90 ? 'bg-red-500' : ($usage['staff']['percentage'] >= 70 ? 'bg-yellow-500' : 'bg-indigo-500') }}"
                                    style="width: {{ $usage['staff']['percentage'] }}%"></div>
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
                                <div class="h-2.5 rounded-full transition-all {{ $usage['customers']['percentage'] >= 90 ? 'bg-red-500' : ($usage['customers']['percentage'] >= 70 ? 'bg-yellow-500' : 'bg-indigo-500') }}"
                                    style="width: {{ $usage['customers']['percentage'] }}%"></div>
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
                                <div class="h-2.5 rounded-full transition-all {{ $usage['orders']['percentage'] >= 90 ? 'bg-red-500' : ($usage['orders']['percentage'] >= 70 ? 'bg-yellow-500' : 'bg-indigo-500') }}"
                                    style="width: {{ $usage['orders']['percentage'] }}%"></div>
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
                        <div
                            class="flex items-start gap-3 p-3 rounded-lg {{ in_array($featureKey, $tenantFeatures) ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200' }}">
                            @if(in_array($featureKey, $tenantFeatures))
                                <svg class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @else
                                <svg class="h-5 w-5 text-gray-300 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            @endif
                            <div>
                                <p
                                    class="text-sm font-medium {{ in_array($featureKey, $tenantFeatures) ? 'text-green-800' : 'text-gray-400' }}">
                                    {{ $featureDef['label'] }}
                                </p>
                                <p
                                    class="text-xs {{ in_array($featureKey, $tenantFeatures) ? 'text-green-600' : 'text-gray-400' }}">
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
                <p class="text-indigo-100 text-sm mb-4">Your free trial ends in <strong>{{ $trialDaysRemaining }}
                        {{ Str::plural('day', $trialDaysRemaining) }}</strong>. Upgrade to continue using all features
                    without interruption.</p>
                <p class="text-xs text-indigo-200">Contact your administrator to upgrade your plan.</p>
            </div>
        @elseif($plan && $plan->isFree() && !$isOnTrial)
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg shadow-sm p-6 text-white">
                <h3 class="text-lg font-bold mb-2">Upgrade to Premium</h3>
                <p class="text-indigo-100 text-sm mb-4">Get unlimited staff, customers, and orders. Plus access to all
                    features including online payments, SMS notifications, and advanced reports.</p>
                <p class="text-xs text-indigo-200">Contact your administrator to upgrade your plan.</p>
            </div>
        @endif

    </div>
</x-tenant-layout>