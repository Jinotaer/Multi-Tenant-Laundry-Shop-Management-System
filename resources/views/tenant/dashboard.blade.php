<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="space-y-6">

        {{-- Welcome --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Welcome, {{ $user->name }}!</h3>
                <p class="text-sm text-gray-500">
                    You are logged in as <span class="font-medium {{ $theme['nav_active_text'] }}">{{ ucfirst($user->role) }}</span>
                    at <span class="font-medium">{{ $shopName }}</span>.
                </p>
            </div>
        </div>

        @if ($user->isOwner() || $user->isStaff())

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('tenant.customers.index') }}"
                    class="bg-white rounded-lg shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
                    <div class="flex-shrink-0 rounded-full {{ $theme['badge_bg'] }} p-3">
                        <svg class="h-6 w-6 {{ $theme['nav_active_text'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Customers</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalCustomers }}</p>
                    </div>
                </a>

                <a href="{{ route('tenant.orders.index') }}"
                    class="bg-white rounded-lg shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
                    <div class="flex-shrink-0 rounded-full bg-blue-100 p-3">
                        <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Total Orders</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalOrders }}</p>
                    </div>
                </a>

                <a href="{{ route('tenant.orders.index', ['status' => 'pending']) }}"
                    class="bg-white rounded-lg shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
                    <div class="flex-shrink-0 rounded-full bg-yellow-100 p-3">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Pending</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $ordersByStatus['pending'] ?? 0 }}</p>
                    </div>
                </a>

                <a href="{{ route('tenant.orders.index', ['status' => 'ready']) }}"
                    class="bg-white rounded-lg shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
                    <div class="flex-shrink-0 rounded-full bg-green-100 p-3">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Ready for Pickup</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $ordersByStatus['ready'] ?? 0 }}</p>
                    </div>
                </a>
            </div>

            {{-- Owner Revenue Cards --}}
            @if ($user->isOwner())
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Today's Revenue</p>
                        <p class="text-2xl font-bold text-green-600 mt-1">₱{{ number_format($todayRevenue, 2) }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Monthly Revenue</p>
                        <p class="text-2xl font-bold text-green-600 mt-1">₱{{ number_format($monthlyRevenue, 2) }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Staff Members</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $staffCount }}</p>
                    </div>
                </div>
            @endif

            {{-- Recent Orders --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-gray-700">Recent Orders</h4>
                    <a href="{{ route('tenant.orders.index') }}" class="text-xs font-medium {{ $theme['nav_active_text'] }} hover:underline">View all →</a>
                </div>
                @if ($recentOrders->isEmpty())
                    <div class="p-6 text-center text-sm text-gray-400">
                        No orders yet.
                        <a href="{{ route('tenant.orders.create') }}" class="{{ $theme['nav_active_text'] }} hover:underline">Create your first order →</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($recentOrders as $order)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            <a href="{{ route('tenant.orders.show', $order) }}" class="font-medium text-gray-900 hover:{{ $theme['nav_active_text'] }}">
                                                {{ $order->order_number }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-gray-700">{{ $order->customer->name }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $order->status_color }}">
                                                {{ $order->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-gray-700">₱{{ number_format($order->total_amount, 2) }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap text-gray-400">{{ $order->due_date?->format('M d') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Enabled Features --}}
            @if ($user->isOwner())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700">Enabled Features</h4>
                    </div>
                    <div class="p-6 flex flex-wrap gap-2">
                        @foreach (config('themes.features', []) as $featureKey => $featureConfig)
                            @feature($featureKey)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                                    <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                    {{ $featureConfig['label'] }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-400">
                                    <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    {{ $featureConfig['label'] }}
                                </span>
                            @endfeature
                        @endforeach
                    </div>
                </div>
            @endif

        @else
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-blue-50 rounded-lg">
                    <p class="text-sm text-blue-700">As a customer, you'll be able to track your laundry orders here.</p>
                </div>
            </div>
        @endif

    </div>
</x-tenant-layout>
