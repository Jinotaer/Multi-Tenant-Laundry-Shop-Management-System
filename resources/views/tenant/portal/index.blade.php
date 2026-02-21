<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Orders</h2>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="space-y-6">
        @if (!$customer)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                <svg class="mx-auto h-12 w-12 text-yellow-400 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <p class="text-sm font-medium text-yellow-800">No customer profile found</p>
                <p class="text-xs text-yellow-600 mt-1">Your account email doesn't match any customer records. Please contact the shop.</p>
            </div>
        @else
            {{-- Active Orders --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Active Orders</h3>
                @if ($activeOrders->isEmpty())
                    <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                        <p class="text-gray-400 text-sm">No active orders.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($activeOrders as $order)
                            <a href="{{ route('tenant.portal.show', $order) }}"
                                class="bg-white rounded-lg shadow-sm p-4 hover:shadow-md transition block">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-semibold text-gray-900">{{ $order->order_number }}</span>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $order->status_color }}">
                                        {{ $order->status_label }}
                                    </span>
                                </div>
                                @if ($order->service)
                                    <p class="text-xs text-gray-500">{{ $order->service->name }}</p>
                                @endif
                                @if ($order->weight)
                                    <p class="text-xs text-gray-500">{{ $order->weight }} kg</p>
                                @endif
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="text-sm font-bold text-gray-900">₱{{ number_format($order->total_amount, 2) }}</span>
                                    <span class="text-xs {{ $order->isPaid() ? 'text-green-600' : 'text-red-500' }}">
                                        {{ $order->isPaid() ? 'Paid' : 'Unpaid' }}
                                    </span>
                                </div>
                                @if ($order->due_date)
                                    <p class="text-xs text-gray-400 mt-1">Due: {{ $order->due_date->format('M d, Y') }}</p>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Order History --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-700">Order History</h3>
                    <form method="GET" class="flex items-center gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order #..."
                            class="rounded-md border-gray-300 text-sm px-3 py-1.5 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </form>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    @if ($orderHistory->isEmpty())
                        <div class="p-8 text-center">
                            <p class="text-gray-400 text-sm">No orders found.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($orderHistory as $order)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('tenant.portal.show', $order) }}" class="font-medium text-gray-900 hover:{{ $theme['nav_active_text'] }}">
                                                    {{ $order->order_number }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $order->service?->name ?? '—' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $order->status_color }}">{{ $order->status_label }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">₱{{ number_format($order->total_amount, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm {{ $order->isPaid() ? 'text-green-600' : 'text-red-500' }}">
                                                {{ $order->isPaid() ? 'Paid' : 'Unpaid' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">{{ $order->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($orderHistory->hasPages())
                            <div class="px-6 py-4 border-t">{{ $orderHistory->links() }}</div>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-tenant-layout>
