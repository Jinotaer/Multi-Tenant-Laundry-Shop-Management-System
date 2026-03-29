<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Renew Subscription') }}
        </h2>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="max-w-3xl mx-auto space-y-6">
        
        {{-- Grace Period Warning --}}
        @if($isInGracePeriod)
            <div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-lg shadow-sm p-6 text-white">
                <div class="flex items-start gap-4">
                    <svg class="h-8 w-8 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <div>
                        <h3 class="text-lg font-bold mb-1">⚠️ Grace Period Active</h3>
                        <p class="text-sm opacity-90">
                            Your subscription has expired. You have <strong>{{ $graceDaysRemaining }} {{ Str::plural('day', $graceDaysRemaining) }}</strong> remaining in your grace period to renew before your account is suspended.
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg shadow-sm p-6 text-white">
                <div class="flex items-start gap-4">
                    <svg class="h-8 w-8 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <div>
                        <h3 class="text-lg font-bold mb-1">🚨 Subscription Expired</h3>
                        <p class="text-sm opacity-90">
                            Your subscription has expired. Renew now to restore full access to your shop.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Plan Details --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Subscription Details</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Shop Name</p>
                        <p class="text-lg font-bold text-gray-900">{{ $shopName }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Current Plan</p>
                        <p class="text-lg font-bold text-gray-900">{{ $plan->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Renewal Price</p>
                        <p class="text-2xl font-extrabold text-indigo-600">{{ $plan->formatted_price }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Billing Cycle</p>
                        <p class="text-lg font-bold text-gray-900 capitalize">{{ $plan->billing_cycle }}</p>
                    </div>
                </div>

                @if($tenant->subscription_expires_at)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-500">Expired On</p>
                        <p class="text-base font-semibold text-red-600">{{ $tenant->subscription_expires_at->format('F d, Y') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Renewal Benefits --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">What You'll Get</h3>
                
                <ul class="space-y-3">
                    <li class="flex items-start gap-3">
                        <svg class="h-6 w-6 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">Full Access Restored</p>
                            <p class="text-sm text-gray-500">Immediate access to your dashboard and all features</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="h-6 w-6 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">{{ $plan->billing_cycle === 'yearly' ? '12 Months' : '30 Days' }} of Service</p>
                            <p class="text-sm text-gray-500">Uninterrupted service for your business</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="h-6 w-6 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">All Premium Features</p>
                            <p class="text-sm text-gray-500">Continue using all features included in your plan</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="h-6 w-6 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">Priority Support</p>
                            <p class="text-sm text-gray-500">Get help when you need it</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Payment Action --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Proceed to Payment</h3>
                <p class="text-sm text-gray-600 mb-6">
                    You will be redirected to our secure payment gateway (PayMongo) to complete your renewal. We accept GCash, GrabPay, Credit/Debit Cards, and PayMaya.
                </p>

                <form method="POST" action="{{ route('tenant.subscription.renew.checkout') }}">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-lg font-bold rounded-lg hover:opacity-90 transition shadow-lg">
                        Renew for {{ $plan->formatted_price }}
                    </button>
                </form>

                <p class="mt-4 text-xs text-gray-500">
                    By proceeding, you agree to renew your subscription for {{ $plan->billing_cycle === 'yearly' ? '12 months' : '30 days' }} at {{ $plan->formatted_price }}.
                </p>
            </div>
        </div>

        {{-- Help Section --}}
        <div class="bg-gray-50 rounded-lg p-6 text-center">
            <p class="text-sm text-gray-600">
                Need help or have questions about your subscription?
            </p>
            <p class="text-sm text-gray-600 mt-1">
                Contact us at <a href="mailto:support@laundrytrack.com" class="text-indigo-600 hover:text-indigo-700 font-medium">support@laundrytrack.com</a>
            </p>
        </div>

    </div>
</x-tenant-layout>
