<x-tenant-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('tenant.orders.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $order->order_number }}</h2>
            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $order->status_color }}">
                {{ $order->status_label }}
            </span>
            @if ($order->payment_status === 'paid')
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800">Paid</span>
            @else
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-800">Unpaid</span>
            @endif
        </div>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="max-w-3xl space-y-6">

        {{-- Quick Actions --}}
        @if (auth()->user()->isOwner() || auth()->user()->isStaff())
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 flex flex-wrap items-center gap-3">
                    {{-- Status Workflow --}}
                    @php
                        $nextStatuses = \App\Models\Order::nextStatusActionsForPlan($order->status);
                    @endphp

                    @foreach ($nextStatuses as $statusKey => $statusLabel)
                        <form method="POST" action="{{ route('tenant.orders.update-status', $order) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $statusKey }}">
                            <button type="submit"
                                class="inline-flex items-center gap-1 rounded-md {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2 text-sm font-medium text-white shadow-sm transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                                {{ $statusLabel }}
                            </button>
                        </form>
                    @endforeach

                    {{-- Payment Action --}}
                    @if ($order->payment_status !== 'paid')
                        <form method="POST" action="{{ route('tenant.orders.mark-paid', $order) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                class="inline-flex items-center gap-1 rounded-md bg-green-600 hover:bg-green-700 px-4 py-2 text-sm font-medium text-white shadow-sm transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                                Mark as Paid
                            </button>
                        </form>
                    @endif

                    {{-- Receipt --}}
                    <a href="{{ route('tenant.orders.receipt', $order) }}" target="_blank"
                        class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 12h.008v.008h-.008V12zm-2.25 0h.008v.008H16.5V12z" /></svg>
                        Print Receipt
                    </a>

                    <a href="{{ route('tenant.orders.edit', $order) }}"
                        class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
                        Edit
                    </a>
                </div>
            </div>
        @endif

        {{-- Order Info --}}
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Order Details</h3>
                @if (auth()->user()->isOwner())
                    <form method="POST" action="{{ route('tenant.orders.destroy', $order) }}"
                        onsubmit="return confirm('Delete this order?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Delete</button>
                    </form>
                @endif
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Customer</p>
                    <a href="{{ route('tenant.customers.show', $order->customer) }}"
                        class="font-medium text-gray-900 hover:{{ $theme['nav_active_text'] }} hover:underline">
                        {{ $order->customer->name }}
                    </a>
                    @if ($order->customer->phone)
                        <p class="text-gray-500 text-xs mt-0.5">{{ $order->customer->phone }}</p>
                    @endif
                </div>
                @if ($order->service)
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Service</p>
                        <p class="text-gray-700 font-medium">{{ $order->service->name }} <span class="text-gray-400 text-xs">({{ $order->service->formatted_price }})</span></p>
                    </div>
                @endif
                @if ($order->weight)
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Weight</p>
                        <p class="text-gray-700">{{ $order->weight }} kg</p>
                    </div>
                @endif
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Status</p>
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $order->status_color }}">
                        {{ $order->status_label }}
                    </span>
                </div>
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Payment</p>
                    @if ($order->isPaid())
                        <span class="text-green-700 font-medium">Paid</span>
                        <span class="text-gray-400 text-xs ml-1">{{ $order->paid_at->format('M d, Y h:i A') }}</span>
                    @else
                        <span class="text-red-600 font-medium">Unpaid</span>
                    @endif
                </div>
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Due Date</p>
                    <p class="text-gray-700">{{ $order->due_date?->format('M d, Y') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Created</p>
                    <p class="text-gray-700">{{ $order->created_at->format('M d, Y h:i A') }}</p>
                </div>
                @if ($order->notes)
                    <div class="sm:col-span-2">
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Notes</p>
                        <p class="text-gray-700">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Items & Total --}}
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700">Laundry Items</h3>
            </div>
            <div class="p-6">
                @if ($order->items && count($order->items))
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                <th class="pb-2 font-medium">Item</th>
                                <th class="pb-2 font-medium text-center">Qty</th>
                                <th class="pb-2 font-medium text-right">Unit Price</th>
                                <th class="pb-2 font-medium text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="py-2 text-gray-900">{{ $item['name'] ?? '—' }}</td>
                                    <td class="py-2 text-center text-gray-600">{{ $item['qty'] ?? 1 }}</td>
                                    <td class="py-2 text-right text-gray-600">₱{{ number_format($item['price'] ?? 0, 2) }}</td>
                                    <td class="py-2 text-right font-medium text-gray-900">
                                        ₱{{ number_format(($item['qty'] ?? 1) * ($item['price'] ?? 0), 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-sm text-gray-400 italic">No additional items recorded.</p>
                @endif

                <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-700">Total Amount</span>
                    <span class="text-lg font-bold text-gray-900">₱{{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</x-tenant-layout>
