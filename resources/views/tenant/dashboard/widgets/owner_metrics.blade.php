<section class="space-y-4" data-widget-key="owner_metrics">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Revenue</p>
        <h3 class="mt-1 text-lg font-semibold text-gray-900">Owner Metrics</h3>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="tenant-card p-5">
            <p class="text-xs uppercase tracking-wide text-gray-500">Today's Revenue</p>
            <p class="mt-2 text-2xl font-bold text-green-600 dark:text-emerald-300">₱{{ number_format($todayRevenue, 2) }}</p>
        </div>
        <div class="tenant-card p-5">
            <p class="text-xs uppercase tracking-wide text-gray-500">Monthly Revenue</p>
            <p class="mt-2 text-2xl font-bold text-green-600 dark:text-emerald-300">₱{{ number_format($monthlyRevenue, 2) }}</p>
        </div>
        <div class="tenant-card p-5">
            <p class="text-xs uppercase tracking-wide text-gray-500">Staff Members</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $staffCount }}</p>
        </div>
    </div>
</section>
