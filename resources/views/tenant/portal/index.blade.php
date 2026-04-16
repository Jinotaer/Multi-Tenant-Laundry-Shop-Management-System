<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-100 leading-tight">My Orders</h2>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="space-y-6">
        @if (!$customer)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center dark:bg-yellow-900/20 dark:border-yellow-700">
                <svg class="mx-auto h-12 w-12 text-yellow-400 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">No customer profile found</p>
                <p class="text-xs text-yellow-600 dark:text-yellow-300 mt-1">Your account email doesn't match any customer records. Please contact the shop.</p>
            </div>
        @else
            {{-- Active Orders --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-300 mb-3">Active Orders</h3>
                @if ($activeOrders->isEmpty())
                    <div class="bg-white rounded-2xl shadow-sm p-8 text-center dark:bg-slate-800">
                        <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="text-gray-400 dark:text-slate-500 text-sm">No active orders at the moment.</p>
                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-2">Your completed orders can be found in the Dashboard.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($activeOrders as $order)
                            <div class="bg-white rounded-2xl shadow-sm p-5 dark:bg-slate-800 dark:hover:bg-slate-750">
                                <a href="{{ route('tenant.portal.show', $order) }}" class="block">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $order->order_number }}</span>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $order->status_color }}">
                                            {{ $order->status_label }}
                                        </span>
                                    </div>
                                    @if ($order->service)
                                        <p class="text-sm text-gray-600 dark:text-slate-400 mb-1">{{ $order->service->name }}</p>
                                    @endif
                                    @if ($order->weight)
                                        <p class="text-xs text-gray-500 dark:text-slate-500 mb-3">{{ $order->weight }} kg</p>
                                    @endif
                                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-slate-700 flex items-center justify-between">
                                        <span class="text-lg font-bold text-gray-900 dark:text-slate-100">₱{{ number_format($order->total_amount, 2) }}</span>
                                        <span class="text-xs font-medium {{ $order->isPaid() ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                            {{ $order->isPaid() ? 'Paid' : 'Unpaid' }}
                                        </span>
                                    </div>
                                    @if ($order->due_date)
                                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-2">Due: {{ $order->due_date->format('M d, Y') }}</p>
                                    @endif
                                </a>
                                @if (!$order->isPaid() && tenant()->hasFeature('online_payments') && tenant()->paymongo_secret_key && $order->canBePaidOnline())
                                    <form method="POST" action="{{ route('tenant.order-payments.checkout', $order) }}" class="mt-3">
                                        @csrf
                                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                            </svg>
                                            Pay Online
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-tenant-layout>
