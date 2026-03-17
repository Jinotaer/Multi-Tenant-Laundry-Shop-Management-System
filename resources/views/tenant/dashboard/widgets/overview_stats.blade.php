<section class="space-y-4" data-widget-key="overview_stats">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Overview</p>
            <h3 class="mt-1 text-lg font-semibold text-gray-900">Overview Stats</h3>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('tenant.customers.index') }}" class="tenant-card group flex items-center gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="flex h-12 w-12 items-center justify-center rounded-full {{ $theme['badge_bg'] }}">
                <svg class="h-6 w-6 {{ $theme['nav_active_text'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Customers</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalCustomers }}</p>
            </div>
        </a>

        <a href="{{ route('tenant.orders.index') }}" class="tenant-card group flex items-center gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/15">
                <svg class="h-6 w-6 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Total Orders</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalOrders }}</p>
            </div>
        </a>

        <a href="{{ route('tenant.orders.index', ['status' => 'pending']) }}" class="tenant-card group flex items-center gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-500/15">
                <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Pending</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $ordersByStatus['pending'] ?? 0 }}</p>
            </div>
        </a>

        <a href="{{ route('tenant.orders.index', ['status' => 'ready']) }}" class="tenant-card group flex items-center gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-500/15">
                <svg class="h-6 w-6 text-green-600 dark:text-green-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Ready for Pickup</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $ordersByStatus['ready'] ?? 0 }}</p>
            </div>
        </a>
    </div>
</section>
