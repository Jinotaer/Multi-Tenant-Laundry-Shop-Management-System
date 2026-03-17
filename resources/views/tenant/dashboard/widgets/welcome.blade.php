<section class="tenant-panel overflow-hidden" data-widget-key="welcome">
    <div class="p-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Welcome</p>
        <h3 class="mt-2 text-lg font-semibold text-gray-900">Welcome, {{ $user->name }}!</h3>
        <p class="mt-2 text-sm text-gray-500">
            You are logged in as <span class="font-medium {{ $theme['nav_active_text'] }}">{{ ucfirst($user->role) }}</span>
            at <span class="font-medium text-gray-900">{{ $shopName }}</span>.
        </p>
    </div>
</section>
