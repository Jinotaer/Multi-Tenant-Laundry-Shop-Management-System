@php
    $theme = tenant()->getThemePreset();
@endphp

<x-tenant-layout>
    @php
        $maxRevenue = max($timeline->pluck('revenue')->all() ?: [1]);
        $maxOrders = max($timeline->pluck('orders')->all() ?: [1]);
        $totalStatuses = array_sum($statusBreakdown);
        $periodLabel = match ($period) {
            '7d' => 'Last 7 days',
            '90d' => 'Last 90 days',
            default => 'Last 30 days',
        };
    @endphp

    <div class="space-y-5">
        <x-tenant-header title="Analytics" description="Track revenue, order volume, customer value, and service demand.">
            <x-slot name="actions">
                <div class="flex flex-wrap gap-2">
                    @foreach (['7d' => 'Last 7 Days', '30d' => 'Last 30 Days', '90d' => 'Last 90 Days'] as $key => $label)
                        <a
                            href="{{ route('tenant.analytics.index', ['period' => $key]) }}"
                            class="rounded-md px-3 py-2 text-sm {{ $period === $key ? $theme['primary_bg'].' text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700' }}"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </x-slot>
        </x-tenant-header>

        <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Revenue</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">PHP {{ number_format($totalRevenue, 2) }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ $periodLabel }}</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Orders</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($totalOrders) }}</p>
                <p class="mt-1 text-sm text-gray-500">Completed and pending workload</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Average Ticket</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">PHP {{ number_format($averageOrderValue, 2) }}</p>
                <p class="mt-1 text-sm text-gray-500">Average paid order value</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Active Signals</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ count($statusBreakdown) }}</p>
                <p class="mt-1 text-sm text-gray-500">Tracked order statuses this period</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.45fr_0.85fr]">
            <section class="rounded-xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Revenue Timeline</h3>
                        <p class="mt-1 text-sm text-gray-500">Daily revenue and order volume for {{ strtolower($periodLabel) }}.</p>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($timeline as $day)
                        <div class="grid gap-3 md:grid-cols-[70px_1fr_88px_1fr_52px] md:items-center">
                            <span class="text-xs font-medium uppercase tracking-[0.14em] text-gray-400">{{ $day['label'] }}</span>
                            <div class="h-2 rounded-full bg-gray-100">
                                <div class="h-2 rounded-full {{ $theme['primary_bg'] }}" style="width: {{ $maxRevenue > 0 ? round(($day['revenue'] / $maxRevenue) * 100) : 0 }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-700">PHP {{ number_format($day['revenue'], 2) }}</span>
                            <div class="h-2 rounded-full bg-gray-100">
                                <div class="h-2 rounded-full bg-gray-400" style="width: {{ $maxOrders > 0 ? round(($day['orders'] / $maxOrders) * 100) : 0 }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-500">{{ $day['orders'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No analytics data available yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-xl bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900">Order Status Mix</h3>
                <p class="mt-1 text-sm text-gray-500">How current orders are distributed by status.</p>

                <div class="mt-6 space-y-4">
                    @forelse ($statusBreakdown as $status => $count)
                        @php
                            $percentage = $totalStatuses > 0 ? round(($count / $totalStatuses) * 100) : 0;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium capitalize text-gray-700">{{ str_replace('_', ' ', $status) }}</span>
                                <span class="text-gray-500">{{ $count }} ({{ $percentage }}%)</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100">
                                <div class="h-2 rounded-full {{ $theme['primary_bg'] }}" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No orders recorded during this period.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="rounded-xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Top Customers</h3>
                    <span class="text-xs uppercase tracking-[0.16em] text-gray-400">By revenue</span>
                </div>

                <div class="mt-5 space-y-4">
                    @forelse ($topCustomers as $customer)
                        <div class="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $customer->name }}</p>
                                <p class="text-xs text-gray-500">{{ $customer->orders_count }} orders this period</p>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">PHP {{ number_format((float) ($customer->orders_sum_total_amount ?? 0), 2) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No customer activity yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Top Services</h3>
                    <span class="text-xs uppercase tracking-[0.16em] text-gray-400">By order count</span>
                </div>

                <div class="mt-5 space-y-4">
                    @forelse ($topServices as $service)
                        <div class="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $service->name }}</p>
                                <p class="text-xs text-gray-500">{{ $service->formatted_price }}</p>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">{{ $service->orders_count }} orders</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No service demand data yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="rounded-xl bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Inventory Watchlist</h3>
                    <p class="mt-1 text-sm text-gray-500">Items currently at or below their reorder level.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $theme['badge_bg'] }} {{ $theme['badge_text'] }}">
                    {{ $lowStockItems->count() }} item{{ $lowStockItems->count() === 1 ? '' : 's' }}
                </span>
            </div>

            <div class="mt-5">
                @if ($lowStockItems->isEmpty())
                    <p class="text-sm text-gray-500">No low-stock items detected for this tenant.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Item</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">On Hand</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Reorder Level</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($lowStockItems as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item->name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $item->quantity_on_hand }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $item->reorder_level }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
        </div>
    </div>
</x-tenant-layout>
