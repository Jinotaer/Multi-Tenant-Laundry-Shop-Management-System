<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upgrade Successful') }}
        </h2>
    </x-slot>

    <div class="tenant-page-stack max-w-2xl mx-auto">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-8 text-center">
                <div class="mb-6">
                    <svg class="h-20 w-20 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h3 class="text-2xl font-bold text-gray-900 mb-2">
                    <svg class="inline-block h-4 w-4 align-text-bottom" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/></svg> Upgrade Successful!
                </h3>
                
                <p class="text-gray-600 mb-6">
                    Your subscription has been upgraded to <strong>{{ $plan->name }}</strong> plan.
                </p>

                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Plan</p>
                            <p class="font-semibold text-gray-900">{{ $plan->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Amount Paid</p>
                            <p class="font-semibold text-gray-900">{{ $payment->formatted_amount }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Payment Date</p>
                            <p class="font-semibold text-gray-900">{{ $payment->paid_at?->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Valid Until</p>
                            <p class="font-semibold text-gray-900">{{ $tenant->subscription_expires_at?->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <!-- <a href="{{ route('tenant.dashboard') }}" class="block w-full sm:inline-block sm:w-auto px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Go to Dashboard
                    </a> -->
                    <a href="{{ route('tenant.subscription') }}" class="block w-full sm:inline-block sm:w-auto px-6 py-3 bg-blue-600 text-gray-700 font-semibold rounded-lg hover:bg-blue-700 transition sm:ml-3">
                        View Subscription
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-tenant-layout>
