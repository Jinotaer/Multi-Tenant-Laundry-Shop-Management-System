<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Renewal Successful') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-8 text-center">
                {{-- Success Icon --}}
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
                    <svg class="h-12 w-12 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h1 class="text-3xl font-bold text-gray-900 mb-2">Subscription Renewed!</h1>
                <p class="text-gray-600 mb-8">Your subscription has been successfully renewed. Thank you for your payment!</p>

                {{-- Payment Details --}}
                <div class="bg-gray-50 rounded-lg p-6 mb-8 text-left">
                    <h3 class="font-semibold text-gray-900 mb-4">Payment Details</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Plan</span>
                            <span class="font-semibold text-gray-900">{{ $plan->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Amount Paid</span>
                            <span class="font-semibold text-gray-900">₱{{ number_format((float) $payment->amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Payment Method</span>
                            <span class="font-semibold text-gray-900 capitalize">{{ $payment->payment_method ?? 'Online Payment' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Payment Date</span>
                            <span class="font-semibold text-gray-900">{{ $payment->paid_at?->format('F d, Y h:i A') ?? now()->format('F d, Y h:i A') }}</span>
                        </div>
                        @if($tenant->subscription_expires_at)
                            <div class="flex justify-between pt-3 border-t border-gray-200">
                                <span class="text-gray-600">Next Renewal Date</span>
                                <span class="font-semibold text-green-600">{{ $tenant->subscription_expires_at->format('F d, Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('tenant.dashboard') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        Go to Dashboard
                    </a>
                    <a href="{{ route('tenant.subscription') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                        View Subscription
                    </a>
                </div>

                <p class="mt-8 text-sm text-gray-500">
                    A confirmation email has been sent to your registered email address.
                </p>
            </div>
        </div>
    </div>
</x-tenant-layout>
