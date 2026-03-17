<section class="tenant-panel overflow-hidden" data-widget-key="recent_orders">
    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-800">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Activity</p>
            <h3 class="mt-1 text-lg font-semibold text-gray-900">Recent Orders</h3>
        </div>
        <a href="{{ route('tenant.orders.index') }}" class="text-xs font-medium {{ $theme['nav_active_text'] }} hover:underline">View all</a>
    </div>

    @if ($recentOrders->isEmpty())
        <div class="p-6 text-center text-sm text-gray-400">
            No orders yet.
            <a href="{{ route('tenant.orders.create') }}" class="{{ $theme['nav_active_text'] }} hover:underline">Create your first order</a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm dark:divide-slate-800">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Order #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Due</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    @foreach ($recentOrders as $order)
                        <tr class="transition hover:bg-gray-50 dark:hover:bg-slate-900">
                            <td class="whitespace-nowrap px-6 py-3">
                                <a href="{{ route('tenant.orders.show', $order) }}" class="font-medium text-gray-900 {{ $theme['nav_active_text'] }} hover:underline">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-6 py-3 text-gray-700">{{ $order->customer->name }}</td>
                            <td class="whitespace-nowrap px-6 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $order->status_color }}">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-3 text-gray-700">₱{{ number_format($order->total_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-gray-400">{{ $order->due_date?->format('M d') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
