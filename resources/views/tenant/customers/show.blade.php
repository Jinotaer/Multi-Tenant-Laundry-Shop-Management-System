<x-tenant-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('tenant.customers.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $customer->name }}</h2>
        </div>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="space-y-6">

        {{-- Customer Details --}}
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Customer Details</h3>
                <a href="{{ route('tenant.customers.edit', $customer) }}"
                    class="text-sm font-medium {{ $theme['nav_active_text'] }} hover:underline">
                    Edit
                </a>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Name</p>
                    <p class="font-medium text-gray-900">{{ $customer->name }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Phone</p>
                    <p class="text-gray-700">{{ $customer->phone ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Email</p>
                    <p class="text-gray-700">{{ $customer->email ?? '—' }}</p>
                </div>
                <!-- <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Address</p>
                    <p class="text-gray-700">{{ $customer->address ?? '—' }}</p>
                </div> -->
                @if ($customer->notes)
                    <div class="sm:col-span-2">
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Notes</p>
                        <p class="text-gray-700">{{ $customer->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Orders --}}
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Orders</h3>
                <a href="{{ route('tenant.orders.create', ['customer_id' => $customer->id]) }}"
                    class="inline-flex items-center gap-1 rounded-md {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-3 py-1.5 text-xs font-medium text-white shadow-sm transition">
                    + New Order
                </a>
            </div>
            @if ($orders->isEmpty())
                <div class="py-10 text-center text-sm text-gray-400">No orders yet for this customer.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="relative px-6 py-3"><span class="sr-only">View</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($orders as $order)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $order->order_number }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $order->status_color }}">
                                            {{ $order->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">₱{{ number_format($order->total_amount, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $order->due_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-400">{{ $order->created_at->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <a href="{{ route('tenant.orders.show', $order) }}" class="{{ $theme['nav_active_text'] }} hover:underline font-medium">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($orders->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $orders->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-tenant-layout>
