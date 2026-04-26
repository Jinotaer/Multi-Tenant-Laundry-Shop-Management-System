<x-admin-layout>
    <x-slot name="header">
        <x-admin-header title="Resource Monitoring" description="Real-time metrics for today's operations.">
            <x-slot name="actions">
                <x-primary-button href="{{ route('admin.monitoring.export') }}">
                    Export CSV
                </x-primary-button>
            </x-slot>
        </x-admin-header>
    </x-slot>

    <div class="admin-page-stack space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="tenant-panel overflow-hidden p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Total Tenants</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($totalTenants) }}</div>
            </div>
            <div class="tenant-panel overflow-hidden p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Total Storage</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-slate-100">
                    {{ $totalStorageMb >= 1024 ? number_format($totalStorageMb / 1024, 2) . ' GB' : number_format($totalStorageMb, 2) . ' MB' }}
                </div>
            </div>
            <div class="tenant-panel overflow-hidden p-6">
                <div class="text-sm font-medium text-gray-500 dark:text-slate-400">Total Bandwidth (Month)</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-slate-100">
                    {{ $totalBandwidthMb >= 1024 ? number_format($totalBandwidthMb / 1024, 2) . ' GB' : number_format($totalBandwidthMb, 2) . ' MB' }}
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="tenant-panel overflow-hidden p-4">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Sort By</label>
                    <select name="sort" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 shadow-sm">
                        <option value="current_storage_mb" {{ $currentSort === 'current_storage_mb' ? 'selected' : '' }}>Storage</option>
                        <option value="current_bandwidth_mb" {{ $currentSort === 'current_bandwidth_mb' ? 'selected' : '' }}>Bandwidth</option>
                        <option value="current_api_requests" {{ $currentSort === 'current_api_requests' ? 'selected' : '' }}>API Requests</option>
                        <option value="created_at" {{ $currentSort === 'created_at' ? 'selected' : '' }}>Created Date</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Direction</label>
                    <select name="direction" class="rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 shadow-sm">
                        <option value="desc" {{ $currentDirection === 'desc' ? 'selected' : '' }}>Highest First</option>
                        <option value="asc" {{ $currentDirection === 'asc' ? 'selected' : '' }}>Lowest First</option>
                    </select>
                </div>
                <x-primary-button type="submit">Filter</x-primary-button>
            </form>
        </div>

        <!-- Tenants Table -->
        <div class="tenant-panel overflow-hidden">
            <div class="p-6 text-gray-900 dark:text-slate-100">
                @if ($tenants->isEmpty())
                    <p class="text-gray-500 dark:text-slate-400">No tenants found.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead class="bg-gray-50 dark:bg-slate-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Shop</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Storage</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Bandwidth</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">API Requests</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                                @foreach ($tenants as $tenant)
                                    @php
                                        $storagePercent = $tenant->getStorageUsagePercentage();
                                        $bandwidthPercent = $tenant->getBandwidthUsagePercentage();
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100">
                                                {{ $tenant->registration->shop_name ?? $tenant->id }}
                                            </div>
                                            <div class="text-xs text-gray-400 dark:text-slate-500">{{ $tenant->id }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                            @if ($tenant->subscriptionPlan)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium tenant-badge-accent">
                                                    {{ $tenant->subscriptionPlan->name }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 dark:text-slate-500">No plan</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-slate-100">
                                                {{ $tenant->formatted_current_storage }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-slate-100">
                                                {{ $tenant->formatted_current_bandwidth }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100">
                                            {{ number_format($tenant->current_api_requests) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('admin.monitoring.show', $tenant) }}" class="tenant-link">
                                                Details
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $tenants->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
