<x-admin-layout>
    @php
        $shopName = $tenant->registration->shop_name ?? $tenant->id;
        $shopInitials = collect(preg_split('/\s+/', trim($shopName)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->implode('');
        $shopInitials = $shopInitials !== '' ? $shopInitials : strtoupper(substr($shopName, 0, 1));

        $storagePercent = $tenant->getStorageUsagePercentage();
        $bandwidthPercent = $tenant->getBandwidthUsagePercentage();

        $storageStatus = match (true) {
            $tenant->isStorageLimitExceeded() => ['label' => 'Exceeded', 'class' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-200'],
            $storagePercent !== null && $storagePercent >= 80 => ['label' => 'Warning', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-200'],
            default => ['label' => 'Normal', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200'],
        };

        $bandwidthResetIn = now()->endOfMonth()->diffInDays(now()) + 1;
        $tenantStatus = $tenant->isEnabled()
            ? ['label' => 'Enabled', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200']
            : ['label' => 'Disabled', 'class' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-200'];
        $planName = $tenant->subscriptionPlan?->name ?? 'No plan';
        $latestSnapshotLabel = $latestMetric
            ? 'Last sample '.$latestMetric->recorded_at->diffForHumans()
            : 'No samples recorded yet';
        $latestSnapshotTimestamp = $latestMetric
            ? $latestMetric->recorded_at->format('M d, Y, h:i A')
            : 'Waiting for first capture';
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('admin.monitoring.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 mb-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    Back to Monitoring
                </a>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100">{{ $shopName }}</h2>
            </div>
            
            <form method="POST" action="{{ route('admin.monitoring.refresh', $tenant) }}" class="m-0">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white text-sm font-semibold rounded-full shadow-sm transition-colors active:scale-95">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh Metrics
                </button>
            </form>
        </div>
    </x-slot>

    <div class="monitoring-show-page admin-page-stack space-y-6">
        {{-- 4 stat cards --}}
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {{-- Storage Used --}}
            <article class="tenant-panel p-5">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Storage Used</p>
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                          style="background: var(--tenant-theme-accent-soft); color: var(--tenant-theme-accent);">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-3 flex flex-wrap items-baseline gap-2">
                    <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $tenant->formatted_current_storage }}</p>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] {{ $storageStatus['class'] }}">
                        {{ $storageStatus['label'] }}
                    </span>
                </div>
                <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div class="h-full rounded-full transition-all"
                         style="width: {{ min(100, max(2, $storagePercent ?? 2)) }}%; background: var(--tenant-theme-accent);"></div>
                </div>
            </article>

            {{-- Bandwidth (Month) --}}
            <article class="tenant-panel p-5">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Bandwidth (Month)</p>
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $tenant->formatted_current_bandwidth }}</p>
                <p class="mt-3 inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                    Resets in {{ $bandwidthResetIn }} {{ \Illuminate\Support\Str::plural('day', $bandwidthResetIn) }}
                </p>
            </article>

            {{-- API Requests --}}
            <article class="tenant-panel p-5">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">API Requests</p>
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold text-slate-900 dark:text-slate-100">
                    {{ number_format($tenant->current_api_requests) }}
                </p>
                <p class="mt-3 text-xs italic text-slate-400 dark:text-slate-500">
                    {{ $tenant->current_api_requests > 0 ? 'requests this month' : 'No traffic recorded' }}
                </p>
            </article>

            {{-- Database Size --}}
            <article class="tenant-panel p-5">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Database Size</p>
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                          style="background: var(--tenant-theme-accent-soft); color: var(--tenant-theme-accent);">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold text-slate-900 dark:text-slate-100">
                    {{ $latestMetric?->formatted_database_size ?? '0.00 MB' }}
                </p>
                <p class="mt-3 inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    @if ($latestMetric)
                        Last updated: {{ $latestMetric->recorded_at->diffForHumans() }}
                    @else
                        No data yet
                    @endif
                </p>
            </article>
        </section>

        {{-- Usage History + Tenant Info --}}
        <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Usage History (chart) --}}
            <div class="tenant-panel p-5 lg:col-span-2">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Usage History</h2>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                            Storage trend over the selected period.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <form method="GET" class="flex items-center">
                            <select name="days"
                                    onchange="this.form.submit()"
                                    class="rounded-full border border-slate-200 bg-slate-50 py-1.5 pl-3 pr-8 text-xs font-medium text-slate-700 shadow-sm focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                <option value="7" {{ $days === 7 ? 'selected' : '' }}>Last 7 days</option>
                                <option value="30" {{ $days === 30 ? 'selected' : '' }}>Last 30 days</option>
                                <option value="90" {{ $days === 90 ? 'selected' : '' }}>Last 90 days</option>
                            </select>
                        </form>

                        <a href="{{ route('admin.monitoring.export') }}"
                           class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
                           title="Download CSV"
                           aria-label="Download CSV">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="mt-5">
                    @if ($metrics->isEmpty())
                        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 px-6 py-14 text-center dark:border-slate-700">
                            <svg class="h-10 w-10 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                            </svg>
                            <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-200">No historical data yet</p>
                            <p class="mt-1 max-w-sm text-xs text-slate-500 dark:text-slate-400">
                                Run <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] dark:bg-slate-800">php artisan tenants:collect-metrics</code> to start recording usage.
                            </p>
                        </div>
                    @else
                        <div class="h-64">
                            <canvas id="usageChart"></canvas>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Tenant info card --}}
            <div class="tenant-panel p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-base font-bold text-white shadow-sm"
                         style="background: linear-gradient(135deg, var(--tenant-theme-accent) 0%, var(--tenant-theme-accent-soft-strong) 100%);">
                        {{ $shopInitials }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-bold text-slate-900 dark:text-slate-100">{{ $shopName }}</h2>
                        <p class="truncate font-mono text-xs text-slate-500 dark:text-slate-400">{{ $tenant->id }}</p>
                    </div>
                </div>

                <dl class="mt-5 space-y-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">Plan</dt>
                        <dd>
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                {{ $tenant->subscriptionPlan?->name ?? 'None' }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">Status</dt>
                        <dd>
                            @if ($tenant->isEnabled())
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">
                                    Enabled
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-red-700 dark:bg-red-500/15 dark:text-red-200">
                                    Disabled
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>

                <dl class="mt-4 space-y-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">Active Users</dt>
                        <dd class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            {{ number_format($latestMetric?->active_users_count ?? 0) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">Total Orders</dt>
                        <dd class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            {{ number_format($latestMetric?->orders_count ?? 0) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">Total Customers</dt>
                        <dd class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            {{ number_format($latestMetric?->customers_count ?? 0) }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-5 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <a href="{{ route('admin.tenants.show', $tenant) }}"
                       class="inline-flex items-center gap-1.5 text-sm font-semibold hover:opacity-80"
                       style="color: var(--tenant-theme-accent);">
                        View Full Tenant Details
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5l6 6m0 0l-6 6m6-6h-15"/>
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        {{-- Historical Log table --}}
        <section class="tenant-panel overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Historical Log</h2>
                <button type="button"
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/>
                    </svg>
                    Filters
                </button>
            </div>

            @if ($paginatedMetrics->isEmpty())
                <div class="px-5 py-12 text-center">
                    <p class="text-sm text-slate-500 dark:text-slate-400">No metric snapshots recorded yet.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                                <th class="px-5 py-3">Date</th>
                                <th class="px-5 py-3">Total Storage</th>
                                <th class="px-5 py-3">Bandwidth</th>
                                <th class="px-5 py-3">API Requests</th>
                                <th class="px-5 py-3">Users</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                            @foreach ($paginatedMetrics as $metric)
                                <tr class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-700 dark:text-slate-200">
                                        {{ $metric->recorded_at->format('M d, Y') }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-500 dark:text-slate-400">
                                        {{ $metric->formatted_total_storage ?? $metric->formatted_storage }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-500 dark:text-slate-400">
                                        {{ $metric->formatted_bandwidth }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                              style="background: var(--tenant-theme-accent-soft); color: var(--tenant-theme-accent);">
                                            {{ number_format($metric->api_requests_count) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-700 dark:text-slate-200">
                                        {{ number_format($metric->active_users_count) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 dark:border-slate-800">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Showing {{ $paginatedMetrics->firstItem() }} to {{ $paginatedMetrics->lastItem() }}
                        of {{ $paginatedMetrics->total() }} {{ \Illuminate\Support\Str::plural('entry', $paginatedMetrics->total()) }}
                    </p>
                    <div>
                        {{ $paginatedMetrics->links() }}
                    </div>
                </div>
            @endif
        </section>
    </div>

    @if ($metrics->isNotEmpty())
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const canvas = document.getElementById('usageChart');
                if (!canvas) return;

                const accent = getComputedStyle(document.documentElement).getPropertyValue('--tenant-theme-accent').trim() || '#3b82f6';
                const chartData = @json($chartData);

                const ctx = canvas.getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 240);
                gradient.addColorStop(0, accent + '33');
                gradient.addColorStop(1, accent + '00');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: 'Storage (MB)',
                            data: chartData.storage,
                            borderColor: accent,
                            backgroundColor: gradient,
                            borderWidth: 2.5,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: accent,
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 2,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.92)',
                                titleColor: '#f8fafc',
                                bodyColor: '#cbd5e1',
                                padding: 10,
                                cornerRadius: 8,
                                displayColors: false,
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    color: '#94a3b8',
                                    font: { size: 10, weight: '600' },
                                    maxRotation: 0,
                                    autoSkipPadding: 18,
                                },
                                border: { display: false },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(148, 163, 184, 0.15)', drawTicks: false },
                                ticks: { display: false },
                                border: { display: false },
                            },
                        },
                    },
                });
            });
        </script>
        @endpush
    @endif
</x-admin-layout>
