<x-tenant-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reports</h2>
            <div class="flex items-center gap-2">
                @foreach (['week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $key => $label)
                    <a href="{{ route('tenant.reports.index', ['period' => $key]) }}"
                        class="px-3 py-1.5 text-sm rounded-md {{ $period === $key ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="space-y-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-5">
                <p class="text-xs font-medium text-gray-500 uppercase">Total Revenue</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">₱{{ number_format($totalRevenue, 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <p class="text-xs font-medium text-gray-500 uppercase">Total Orders</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalOrders }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $paidOrders }} paid · {{ $unpaidOrders }} unpaid</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <p class="text-xs font-medium text-gray-500 uppercase">Avg. Order Value</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">₱{{ number_format($averageOrderValue, 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <p class="text-xs font-medium text-gray-500 uppercase">Total Customers</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalCustomers }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Orders by Status --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Orders by Status</h3>
                @php
                    $statusLabels = \App\Models\Order::statusLabels();
                    $statusColors = \App\Models\Order::statusColors();
                @endphp
                <div class="space-y-3">
                    @foreach ($statusLabels as $key => $label)
                        @php
                            $count = $ordersByStatus[$key] ?? 0;
                            $percentage = $totalOrders > 0 ? round(($count / $totalOrders) * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-600">{{ $label }}</span>
                                <span class="font-medium text-gray-900">{{ $count }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="h-2 rounded-full {{ str_replace(['text-yellow-800', 'text-blue-800', 'text-purple-800', 'text-green-800', 'text-gray-800'], ['bg-yellow-400', 'bg-blue-400', 'bg-purple-400', 'bg-green-400', 'bg-gray-400'], $statusColors[$key] ?? 'bg-gray-400') }}"
                                    style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Popular Services --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Popular Services</h3>
                @if ($popularServices->isEmpty())
                    <p class="text-sm text-gray-400 italic">No services yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($popularServices as $service)
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $service->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $service->formatted_price }}</p>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">{{ $service->orders_count }} orders</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Daily Revenue (simple table chart) --}}
        @if (!empty($dailyRevenue))
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Daily Revenue (Last 30 Days)</h3>
                @php $maxRevenue = max(array_values($dailyRevenue) ?: [1]); @endphp
                <div class="space-y-1.5">
                    @foreach ($dailyRevenue as $date => $revenue)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 w-20 flex-shrink-0">{{ \Carbon\Carbon::parse($date)->format('M d') }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-4 relative">
                                <div class="h-4 rounded-full {{ $theme['primary_bg'] }}"
                                    style="width: {{ $maxRevenue > 0 ? round(($revenue / $maxRevenue) * 100) : 0 }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 w-24 text-right">₱{{ number_format($revenue, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Recent Orders --}}
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700">Recent Orders</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($recentOrders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <a href="{{ route('tenant.orders.show', $order) }}" class="text-sm font-medium text-gray-900 hover:{{ $theme['nav_active_text'] }}">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $order->customer?->name ?? '—' }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">{{ $order->service?->name ?? '—' }}</td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $order->status_color }}">{{ $order->status_label }}</span>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">₱{{ number_format($order->total_amount, 2) }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm {{ $order->isPaid() ? 'text-green-600' : 'text-red-500' }}">
                                    {{ $order->isPaid() ? 'Paid' : 'Unpaid' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-tenant-layout>
