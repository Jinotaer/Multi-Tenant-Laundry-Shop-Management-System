<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <a href="{{ route('admin.monitoring.index') }}" class="text-sm text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300">
                    ← Back to Monitoring
                </a>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight mt-1">
                    {{ $tenant->registration->shop_name ?? $tenant->id }}
                </h2>
            </div>
            <form method="POST" action="{{ route('admin.monitoring.refresh', $tenant) }}" class="inline">
                @csrf
                <x-primary-button type="submit">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh Metrics
                </x-primary-button>
            </form>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Current Usage Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Storage -->
            <div class="tenant-panel overflow-hidden p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Storage Used</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-slate-100 mt-2">
                    {{ $tenant->formatted_current_storage }}
                </div>
            </div>

            <!-- Bandwidth -->
            <div class="tenant-panel overflow-hidden p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Bandwidth (Month)</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-slate-100 mt-2">
                    {{ $tenant->formatted_current_bandwidth }}
                </div>
            </div>

            <!-- API Requests -->
            <div class="tenant-panel overflow-hidden p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">API Requests (Month)</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-slate-100 mt-2">
                    {{ number_format($tenant->current_api_requests) }}
                </div>
                <div class="text-sm text-gray-500 dark:text-slate-400">requests this month</div>
            </div>

            <!-- Database Size -->
            <div class="tenant-panel overflow-hidden p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Database Size</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-slate-100 mt-2">
                    {{ $latestMetric?->formatted_database_size ?? '0.00 MB' }}
                </div>
                <div class="text-sm text-gray-500 dark:text-slate-400">
                    @if ($latestMetric)
                        Last updated: {{ $latestMetric->recorded_at->diffForHumans() }}
                    @else
                        No data yet
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Tenant Info -->
            <div class="tenant-panel overflow-hidden p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">Tenant Details</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Tenant ID</dt>
                        <dd class="text-sm text-gray-900 dark:text-slate-100">{{ $tenant->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Plan</dt>
                        <dd class="text-sm text-gray-900 dark:text-slate-100">
                            {{ $tenant->subscriptionPlan?->name ?? 'None' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Status</dt>
                        <dd class="mt-1">
                            @if ($tenant->isEnabled())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Enabled</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Disabled</span>
                            @endif
                        </dd>
                    </div>
                    @if ($latestMetric)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Active Users</dt>
                        <dd class="text-sm text-gray-900 dark:text-slate-100">{{ number_format($latestMetric->active_users_count) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Total Orders</dt>
                        <dd class="text-sm text-gray-900 dark:text-slate-100">{{ number_format($latestMetric->orders_count) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Total Customers</dt>
                        <dd class="text-sm text-gray-900 dark:text-slate-100">{{ number_format($latestMetric->customers_count) }}</dd>
                    </div>
                    @endif
                </dl>

                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-slate-700">
                    <a href="{{ route('admin.tenants.show', $tenant) }}" class="tenant-link text-sm">
                        View Full Tenant Details →
                    </a>
                </div>
            </div>
        </div>

        <!-- Historical Chart -->
        <div class="tenant-panel overflow-hidden p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Usage History</h3>
                <form method="GET" class="flex items-center gap-2">
                    <select name="days" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 days</option>
                        <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 days</option>
                        <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 days</option>
                    </select>
                </form>
            </div>

            @if ($metrics->isEmpty())
                <p class="text-gray-500 dark:text-slate-400 text-center py-8">
                    No historical data available yet. Run <code class="bg-gray-100 dark:bg-slate-800 px-2 py-1 rounded">php artisan tenants:collect-metrics</code> to collect metrics.
                </p>
            @else
                <div class="h-64">
                    <canvas id="usageChart"></canvas>
                </div>
            @endif
        </div>

        <!-- Metrics History Table -->
        @if ($metrics->isNotEmpty())
        <div class="tenant-panel overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">Detailed History</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Total Storage</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Bandwidth</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">API Requests</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Users</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach ($metrics->reverse()->take(10) as $metric)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100">
                                        {{ $metric->recorded_at->format('M d, Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                        {{ $metric->formatted_total_storage }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                        {{ $metric->formatted_bandwidth }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                        {{ number_format($metric->api_requests_count) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                        {{ number_format($metric->active_users_count) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    @if ($metrics->isNotEmpty())
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('usageChart').getContext('2d');
            const chartData = @json($chartData);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: 'Storage (MB)',
                            data: chartData.storage,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: 'Database (MB)',
                            data: chartData.database,
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: 'Bandwidth (MB)',
                            data: chartData.bandwidth,
                            borderColor: 'rgb(245, 158, 11)',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            tension: 0.3,
                            fill: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Size (MB)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        });
    </script>
    @endpush
    @endif
</x-admin-layout>
