<x-tenant-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('tenant.portal.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $order->order_number }}</h2>
            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $order->status_color }}">
                {{ $order->status_label }}
            </span>
        </div>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="max-w-3xl space-y-6">
        {{-- Status Progress --}}
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Order Progress</h3>
            @php
                $steps = ['pending' => 'Received', 'washing' => 'Washing', 'drying' => 'Drying', 'ready' => 'Ready for Pickup', 'delivered' => 'Delivered'];
                $currentIndex = array_search($order->status, array_keys($steps));
            @endphp
            <div class="flex items-center justify-between">
                @foreach ($steps as $key => $label)
                    @php $stepIndex = array_search($key, array_keys($steps)); @endphp
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                            {{ $stepIndex <= $currentIndex ? $theme['primary_bg'] . ' text-white' : 'bg-gray-200 text-gray-400' }}">
                            @if ($stepIndex < $currentIndex)
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            @else
                                {{ $stepIndex + 1 }}
                            @endif
                        </div>
                        <span class="text-xs mt-1 {{ $stepIndex <= $currentIndex ? 'text-gray-900 font-medium' : 'text-gray-400' }}">{{ $label }}</span>
                    </div>
                    @if (!$loop->last)
                        <div class="flex-1 h-0.5 {{ $stepIndex < $currentIndex ? $theme['primary_bg'] : 'bg-gray-200' }} mx-1 mt-[-16px]"></div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Order Details --}}
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700">Order Details</h3>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                @if ($order->service)
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Service</p>
                        <p class="text-gray-700 font-medium">{{ $order->service->name }}</p>
                    </div>
                @endif
                @if ($order->weight)
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Weight</p>
                        <p class="text-gray-700">{{ $order->weight }} kg</p>
                    </div>
                @endif
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Total Amount</p>
                    <p class="text-gray-900 font-bold text-lg">₱{{ number_format($order->total_amount, 2) }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Payment</p>
                    @if ($order->isPaid())
                        <span class="text-green-700 font-medium">Paid</span>
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

        {{-- Items --}}
        @if ($order->items && count($order->items))
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-700">Items</h3>
                </div>
                <div class="p-6">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-200">
                                <th class="pb-2 font-medium">Item</th>
                                <th class="pb-2 font-medium text-center">Qty</th>
                                <th class="pb-2 font-medium text-right">Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="py-2 text-gray-900">{{ $item['name'] ?? '—' }}</td>
                                    <td class="py-2 text-center text-gray-600">{{ $item['qty'] ?? 1 }}</td>
                                    <td class="py-2 text-right text-gray-600">₱{{ number_format(($item['qty'] ?? 1) * ($item['price'] ?? 0), 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-tenant-layout>
