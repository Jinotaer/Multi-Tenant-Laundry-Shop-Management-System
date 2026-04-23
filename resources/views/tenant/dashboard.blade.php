<x-tenant-layout>
    @if ($user instanceof \App\Models\User)
    @php
        $activeOrderCount = collect(\App\Models\Order::activeProcessingStatusesForPlan())
            ->sum(fn (string $s): int => (int) ($ordersByStatus[$s] ?? 0));
        $readyCount = (int) ($ordersByStatus['ready'] ?? 0);

        // SVG line chart: map weekly revenue to 0–100 coordinate space
        $maxRev = max(array_column($weeklyRevenueDays, 'amount'));
        $maxRev = $maxRev > 0 ? $maxRev : 1;
        $svgPoints = [];
        foreach ($weeklyRevenueDays as $idx => $day) {
            $x = count($weeklyRevenueDays) > 1 ? ($idx / (count($weeklyRevenueDays) - 1)) * 100 : 50;
            $y = 92 - (($day['amount'] / $maxRev) * 78); // range 14–92
            $svgPoints[] = [$x, round($y, 2)];
        }
        $linePath = 'M ' . collect($svgPoints)->map(fn ($p) => "{$p[0]},{$p[1]}")->implode(' L ');
        $fillPath = $linePath . ' L 100,100 L 0,100 Z';

        // Peak hours: normalise for bar height
        $maxHour = max(array_column($peakHours, 'count'));
        $maxHour = $maxHour > 0 ? $maxHour : 1;

        // Top services totals for percentage bars
        $totalServiceOrders = $topServices->sum('order_count');
        $totalServiceOrders = $totalServiceOrders > 0 ? $totalServiceOrders : 1;
    @endphp

    <div class="space-y-5">

        {{-- ── Page header ──────────────────────────────────────────────── --}}
        <x-tenant-header title="Overview" description="Real-time metrics for today's operations." />

        {{-- ── Row 1: KPI Cards ─────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Today's Revenue --}}
            <a href="{{ route('tenant.orders.index') }}"
               class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm hover:shadow-md transition-shadow group">
                <div class="absolute -top-4 -right-4 h-24 w-24 rounded-full opacity-[0.07] group-hover:opacity-[0.11] transition-opacity"
                     style="background: var(--tenant-theme-accent);"></div>
                <div class="flex items-start justify-between mb-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">Today's Revenue</p>
                    <div class="flex h-8 w-8 items-center justify-center rounded-full"
                         style="background: var(--tenant-theme-accent-soft);">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                             style="color: var(--tenant-theme-accent);">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-gray-900 dark:text-slate-100 tracking-tight">
                    ₱{{ number_format($todayRevenue, 0) }}<span class="text-base font-normal text-gray-400 dark:text-slate-500">.{{ substr(number_format($todayRevenue, 2), -2) }}</span>
                </p>
                <p class="mt-2 text-xs text-gray-500 dark:text-slate-400">
                    ₱{{ number_format($monthlyRevenue, 0) }} this month
                </p>
            </a>

            {{-- Active Orders --}}
            <a href="{{ route('tenant.orders.index') }}"
               class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm hover:shadow-md transition-shadow group">
                <div class="absolute -top-4 -right-4 h-24 w-24 rounded-full bg-blue-500 opacity-[0.07] group-hover:opacity-[0.11] transition-opacity"></div>
                <div class="flex items-start justify-between mb-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">Active Orders</p>
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/20">
                        <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-gray-900 dark:text-slate-100 tracking-tight">{{ $activeOrderCount }}</p>
                <p class="mt-2 text-xs text-blue-600 dark:text-blue-400 font-medium">{{ $readyCount }} ready for pickup</p>
            </a>

            {{-- Ready for Pickup --}}
            <a href="{{ route('tenant.orders.index', ['status' => 'ready']) }}"
               class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm hover:shadow-md transition-shadow group">
                <div class="absolute -top-4 -right-4 h-24 w-24 rounded-full bg-amber-400 opacity-[0.10] group-hover:opacity-[0.15] transition-opacity"></div>
                <div class="flex items-start justify-between mb-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">Pending Pickup</p>
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/20">
                        <svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-gray-900 dark:text-slate-100 tracking-tight">{{ $readyCount }}</p>
                @php $overdueCount = (int) ($ordersByStatus['overdue'] ?? 0); @endphp
                @if($overdueCount > 0)
                    <p class="mt-2 text-xs text-red-500 dark:text-red-400 font-medium flex items-center gap-1">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        {{ $overdueCount }} overdue
                    </p>
                @else
                    <p class="mt-2 text-xs text-gray-500 dark:text-slate-400">All on time</p>
                @endif
            </a>

            {{-- Total Customers --}}
            <a href="{{ route('tenant.customers.index') }}"
               class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm hover:shadow-md transition-shadow group">
                <div class="absolute -top-4 -right-4 h-24 w-24 rounded-full bg-emerald-500 opacity-[0.07] group-hover:opacity-[0.11] transition-opacity"></div>
                <div class="flex items-start justify-between mb-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">Customers</p>
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-500/20">
                        <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-gray-900 dark:text-slate-100 tracking-tight">{{ $totalCustomers }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-slate-400">{{ $staffCount }} staff members</p>
            </a>

        </div>

        {{-- ── Row 2: Revenue Chart + Peak Hours ───────────────────────── --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">

            {{-- Weekly Revenue Line Chart --}}
            <div class="lg:col-span-8 rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Weekly Revenue Trend</h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">Paid orders over the last 7 days</p>
                    </div>
                    <a href="{{ route('tenant.orders.index') }}"
                       class="text-xs font-semibold hover:underline" style="color: var(--tenant-theme-accent);">View all orders</a>
                </div>

                <div class="relative mt-2" style="height: 180px;">
                    {{-- Y-axis labels --}}
                    <div class="absolute -left-1 top-0 h-full flex flex-col justify-between text-right pr-3 text-[10px] text-gray-400 dark:text-slate-500 pointer-events-none">
                        <span>₱{{ number_format($maxRev, 0) }}</span>
                        <span>₱{{ number_format($maxRev / 2, 0) }}</span>
                        <span>₱0</span>
                    </div>

                    <div class="ml-8 relative h-full border-l border-b border-gray-200 dark:border-slate-700">
                        {{-- Grid lines --}}
                        <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                            <div class="w-full border-b border-dashed border-gray-100 dark:border-slate-800"></div>
                            <div class="w-full border-b border-dashed border-gray-100 dark:border-slate-800"></div>
                            <div class="w-full h-0"></div>
                        </div>

                        {{-- SVG Chart --}}
                        <svg class="absolute inset-0 h-full w-full overflow-visible" preserveAspectRatio="none" viewBox="0 0 100 100">
                            <defs>
                                <linearGradient id="rev-gradient" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="var(--tenant-theme-accent)" stop-opacity="0.25"/>
                                    <stop offset="100%" stop-color="var(--tenant-theme-accent)" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <path d="{{ $fillPath }}" fill="url(#rev-gradient)"/>
                            <path d="{{ $linePath }}" fill="none" stroke="var(--tenant-theme-accent)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            @foreach ($svgPoints as $i => $pt)
                                @if($weeklyRevenueDays[$i]['amount'] > 0)
                                    <circle cx="{{ $pt[0] }}" cy="{{ $pt[1] }}" r="2.5"
                                            fill="white" stroke="var(--tenant-theme-accent)" stroke-width="1.8"/>
                                @endif
                            @endforeach
                        </svg>

                        {{-- X-axis labels --}}
                        <div class="absolute -bottom-6 left-0 w-full flex justify-between text-[10px] text-gray-400 dark:text-slate-500">
                            @foreach ($weeklyRevenueDays as $day)
                                <span>{{ $day['label'] }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Peak Hours Bar Chart --}}
            <div class="lg:col-span-4 rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                <div class="mb-6">
                    <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Peak Drop-off Hours</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">Orders received by hour today</p>
                </div>
                <div class="flex items-end gap-2 h-36">
                    @foreach ($peakHours as $hour)
                        @php $pct = max(8, round(($hour['count'] / $maxHour) * 100)); @endphp
                        <div class="flex-1 flex flex-col items-center justify-end gap-1.5 h-full group">
                            <div class="w-full rounded-t-sm transition-all duration-300"
                                 style="height: {{ $pct }}%; background: {{ $pct >= 90 ? 'var(--tenant-theme-accent)' : 'var(--tenant-theme-accent-soft-strong)' }};"></div>
                            <span class="text-[10px] font-medium {{ $pct >= 90 ? 'text-gray-700 dark:text-slate-200' : 'text-gray-400 dark:text-slate-500' }}">
                                {{ $hour['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
                @if($maxHour <= 1)
                    <p class="mt-3 text-center text-xs text-gray-400 dark:text-slate-500 italic">No orders today yet</p>
                @endif
            </div>

        </div>

        {{-- ── Row 3: Recent Orders + Top Services ─────────────────────── --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">

            {{-- Recent Orders Table --}}
            <div class="lg:col-span-8 rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-800">
                    <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Recent Orders</h3>
                    <a href="{{ route('tenant.orders.index') }}"
                       class="inline-flex items-center gap-1 text-xs font-semibold hover:underline" style="color: var(--tenant-theme-accent);">
                        View all
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/>
                        </svg>
                    </a>
                </div>

                @if ($recentOrders->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <svg class="h-10 w-10 text-gray-300 dark:text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />
                        </svg>
                        <p class="text-sm text-gray-400 dark:text-slate-500">No orders yet.</p>
                        <a href="{{ route('tenant.orders.create') }}" class="mt-1 text-xs font-semibold hover:underline" style="color: var(--tenant-theme-accent);">Create the first order</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-slate-800/60 border-b border-gray-100 dark:border-slate-800">
                                    <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">Order #</th>
                                    <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">Customer</th>
                                    <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">Service</th>
                                    <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">Status</th>
                                    <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-slate-800">
                                @foreach ($recentOrders as $order)
                                    <tr class="hover:bg-gray-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-5 py-3.5">
                                            <a href="{{ route('tenant.orders.show', $order) }}"
                                               class="text-sm font-semibold hover:underline" style="color: var(--tenant-theme-accent);">
                                                {{ $order->order_number }}
                                            </a>
                                        </td>
                                        <td class="px-5 py-3.5 text-sm text-gray-700 dark:text-slate-300">{{ $order->customer?->name ?? '—' }}</td>
                                        <td class="px-5 py-3.5 text-sm text-gray-500 dark:text-slate-400">{{ $order->service?->name ?? '—' }}</td>
                                        <td class="px-5 py-3.5">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $order->status_color }}">
                                                {{ $order->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 text-sm font-semibold text-gray-700 dark:text-slate-300 text-right">
                                            ₱{{ number_format($order->total_amount, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Top Services --}}
            <div class="lg:col-span-4 rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                <h3 class="text-base font-bold text-gray-900 dark:text-slate-100 mb-5">Top Service Types</h3>

                @if ($topServices->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-slate-500 text-center py-6 italic">No order data yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($topServices as $i => $svc)
                            @php
                                $pct = round(($svc->order_count / $totalServiceOrders) * 100);
                                $colors = [
                                    [
                                        'bar' => 'var(--tenant-theme-accent)',
                                        'text' => 'text-gray-900 dark:text-slate-100',
                                        'pct' => 'font-semibold',
                                    ],
                                    ['bar' => '#10b981', 'text' => 'text-gray-700 dark:text-slate-300', 'pct' => ''],
                                    ['bar' => '#f59e0b', 'text' => 'text-gray-700 dark:text-slate-300', 'pct' => ''],
                                    ['bar' => '#8b5cf6', 'text' => 'text-gray-700 dark:text-slate-300', 'pct' => ''],
                                ];
                                $color = $colors[$i] ?? $colors[3];
                            @endphp
                            <div>
                                <div class="flex justify-between items-center mb-1.5">
                                    <span class="text-sm font-medium {{ $color['text'] }} truncate pr-2">
                                        {{ $svc->service?->name ?? 'Unknown' }}
                                    </span>
                                    <span class="text-sm {{ $color['pct'] }} shrink-0" style="color: {{ $color['bar'] }}">
                                        {{ $pct }}%
                                    </span>
                                </div>
                                <div class="h-1.5 rounded-full overflow-hidden bg-gray-100 dark:bg-slate-800">
                                    <div class="h-full rounded-full transition-all duration-500"
                                         style="width: {{ $pct }}%; background: {{ $color['bar'] }};"></div>
                                </div>
                                <p class="mt-1 text-right text-[11px] text-gray-400 dark:text-slate-500">
                                    {{ $svc->order_count }} orders · ₱{{ number_format($svc->total_revenue, 0) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Insight callout --}}
                @if ($topServices->isNotEmpty())
                    <div class="mt-5 p-3 rounded-xl border border-blue-100 dark:border-blue-900/50 bg-blue-50/60 dark:bg-blue-900/20 flex gap-3 items-start">
                        <svg class="h-4 w-4 mt-0.5 shrink-0 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                        </svg>
                        <div>
                            <p class="text-xs font-semibold text-blue-700 dark:text-blue-300">Top service</p>
                            <p class="text-xs text-blue-600/80 dark:text-blue-400/80 mt-0.5">
                                <strong>{{ $topServices->first()?->service?->name }}</strong> drives
                                {{ round(($topServices->first()->order_count / $totalServiceOrders) * 100) }}% of all orders.
                            </p>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>

    @else
        {{-- Customer view --}}
        <div class="rounded-2xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
            <p class="text-sm text-gray-600 dark:text-slate-400">As a customer, you'll be able to track your laundry orders here.</p>
        </div>
    @endif
</x-tenant-layout>
