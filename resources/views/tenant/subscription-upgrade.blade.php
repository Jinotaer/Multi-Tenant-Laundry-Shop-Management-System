<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upgrade Subscription') }}
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        
        {{-- Plan Comparison --}}
        @if($currentPlan)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Plan Comparison</h3>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 mb-2">Current Plan</p>
                            <p class="text-xl font-bold text-gray-900">{{ $currentPlan->name }}</p>
                            <p class="text-lg text-gray-600">{{ $currentPlan->formatted_price }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-2">New Plan</p>
                            <p class="text-xl font-bold text-indigo-600">{{ $newPlan->name }}</p>
                            <p class="text-lg text-indigo-600">{{ $newPlan->formatted_price }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- New Plan Details --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $newPlan->name }} Plan</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Shop Name</p>
                        <p class="text-lg font-bold text-gray-900">{{ $shopName }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Upgrade Price</p>
                        <p class="text-2xl font-extrabold text-indigo-600">{{ $newPlan->formatted_price }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Billing Cycle</p>
                        <p class="text-lg font-bold text-gray-900 capitalize">{{ $newPlan->billing_cycle }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Plan Limits</p>
                        <div class="text-sm text-gray-900">
                            <p>Staff: {{ $newPlan->staff_limit_display }}</p>
                            <p>Customers: {{ $newPlan->customer_limit_display }}</p>
                            <p>Orders: {{ $newPlan->order_limit_display }}/mo</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Features --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">What You'll Get</h3>
                
                <ul class="space-y-3">
                    <li class="flex items-start gap-3">
                        <svg class="h-6 w-6 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">Immediate Upgrade</p>
                            <p class="text-sm text-gray-500">Access all premium features right away</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="h-6 w-6 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">{{ $newPlan->billing_cycle === 'yearly' ? '12 Months' : '30 Days' }} of Service</p>
                            <p class="text-sm text-gray-500">Full access to {{ $newPlan->name }} features</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="h-6 w-6 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">Increased Limits</p>
                            <p class="text-sm text-gray-500">More staff, customers, and orders</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="h-6 w-6 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">Premium Features</p>
                            <p class="text-sm text-gray-500">Advanced analytics, reports, and more</p>
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
                    You will be redirected to our secure payment gateway (PayMongo) to complete your upgrade. We accept GCash, GrabPay, Credit/Debit Cards, and PayMaya.
                </p>

                <form method="POST" action="{{ route('tenant.subscription.upgrade.checkout') }}">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $newPlan->id }}">
                    <button type="submit" class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-lg font-bold rounded-lg hover:opacity-90 transition shadow-lg">
                        Upgrade for {{ $newPlan->formatted_price }}
                    </button>
                </form>

                <p class="mt-4 text-xs text-gray-500">
                    By proceeding, you agree to upgrade to {{ $newPlan->name }} plan for {{ $newPlan->billing_cycle === 'yearly' ? '12 months' : '30 days' }} at {{ $newPlan->formatted_price }}.
                </p>
            </div>
        </div>

        {{-- Help Section --}}
        <div class="bg-gray-50 rounded-lg p-6 text-center">
            <p class="text-sm text-gray-600">
                Need help or have questions about upgrading?
            </p>
            <p class="text-sm text-gray-600 mt-1">
                Contact us at <a href="mailto:support@laundrytrack.com" class="text-indigo-600 hover:text-indigo-700 font-medium">support@laundrytrack.com</a>
            </p>
        </div>

    </div>
</x-tenant-layout>
