@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Storage;

    $layoutSettingsService = app(\App\Services\LayoutSettingsService::class);
    $currentUser = Auth::user();
    $theme = app(\App\Services\ThemeService::class)->getTenantTheme();
    $resolvedLayout = $layoutSettingsService->resolve(tenant(), $currentUser);
    $shopName = tenant('data')['shop_name'] ?? config('app.name', 'LaundryTrack');
    $logoUrl = tenant()->logo_path && Storage::disk('public')->exists(tenant()->logo_path)
        ? asset('storage/' . ltrim(tenant()->logo_path, '/'))
        : null;
    $isSidebarRight = $resolvedLayout['sidebar_position'] === 'right';
    $isCompactSidebar = $resolvedLayout['sidebar_style'] === 'compact';
    $isFloatingSidebar = $resolvedLayout['sidebar_style'] === 'floating';
    $mobileClosedSidebarClass = $isSidebarRight ? 'translate-x-full' : '-translate-x-full';
    $desktopSidebarWidthClass = $isCompactSidebar ? 'lg:w-20' : 'lg:w-72';
    $contentOffsetClass = match (true) {
        $isSidebarRight && $isCompactSidebar => 'lg:pr-28',
        $isSidebarRight && $isFloatingSidebar => 'lg:pr-[22rem]',
        $isSidebarRight => 'lg:pr-72',
        ! $isSidebarRight && $isCompactSidebar => 'lg:pl-28',
        ! $isSidebarRight && $isFloatingSidebar => 'lg:pl-[22rem]',
        default => 'lg:pl-72',
    };
    $desktopSidebarPositionClass = $isSidebarRight ? 'lg:right-0 lg:left-auto' : 'lg:left-0';
    $mobileSidebarPositionClass = $isSidebarRight ? 'right-0 left-auto' : 'left-0';
    $floatingDesktopPositionClass = $isFloatingSidebar
        ? ($isSidebarRight ? 'lg:right-4 lg:left-auto lg:inset-y-4' : 'lg:left-4 lg:inset-y-4')
        : '';
    $sidebarSurfaceClasses = $isFloatingSidebar
        ? 'rounded-3xl border border-gray-200 bg-white/95 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/95'
        : (($isSidebarRight ? 'border-l' : 'border-r') . ' border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900');
    $logoVisible = $resolvedLayout['logo_visibility'] && $logoUrl !== null;
    $topbarSurfaceClass = match ($resolvedLayout['topbar_style']) {
        'card' => 'tenant-topbar tenant-topbar-card px-4 py-4 sm:px-6',
        'accent' => 'tenant-topbar tenant-topbar-accent px-4 py-4 sm:px-6',
        default => 'tenant-topbar tenant-topbar-minimal px-4 py-4 sm:px-6',
    };
    $topbarSpacingClass = in_array($resolvedLayout['topbar_style'], ['card', 'accent'], true) ? 'pt-4 pb-4' : 'pb-4';
    $topbarWrapperClass = $resolvedLayout['topbar_behavior'] === 'sticky'
        ? "sticky top-0 z-30 {$topbarSpacingClass}"
        : $topbarSpacingClass;
    $navLabelVisibilityClass = $isCompactSidebar ? 'lg:hidden' : '';
    $navAlignmentClass = $isCompactSidebar ? 'lg:justify-center' : '';
    $iconSpacingClass = $isCompactSidebar ? 'mr-3 h-5 w-5 lg:mr-0' : 'mr-3 h-5 w-5';
    $activeNavClass = 'tenant-nav-active';
    $inactiveNavClass = 'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white';
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="{{ $resolvedLayout['theme'] }}"
    data-sidebar-position="{{ $resolvedLayout['sidebar_position'] }}"
    data-topbar-behavior="{{ $resolvedLayout['topbar_behavior'] }}"
    data-topbar-style="{{ $resolvedLayout['topbar_style'] }}"
    data-sidebar-style="{{ $resolvedLayout['sidebar_style'] }}"
    data-color-mode="{{ $resolvedLayout['color_mode'] }}"
    data-font-size="{{ $resolvedLayout['font_size'] }}"
    data-border-radius="{{ $resolvedLayout['border_radius'] }}"
    data-logo-visibility="{{ $resolvedLayout['logo_visibility'] ? 'true' : 'false' }}"
    style="font-size: {{ $layoutSettingsService->fontSizeValue($resolvedLayout['font_size']) }}; --tenant-radius: {{ $layoutSettingsService->borderRadiusValue($resolvedLayout['border_radius']) }}; --tenant-theme-accent: {{ $theme['preview'] ?? '#6366f1' }}; --tenant-theme-accent-soft: {{ ($theme['preview'] ?? '#6366f1') . '18' }}; --tenant-theme-accent-soft-strong: {{ ($theme['preview'] ?? '#6366f1') . '30' }};"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        <title>{{ $shopName }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            (() => {
                const colorMode = @js($resolvedLayout['color_mode']);
                const root = document.documentElement;
                const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

                const applyColorMode = () => {
                    const shouldUseDark = colorMode === 'dark' || (colorMode === 'system' && mediaQuery.matches);
                    root.classList.toggle('dark', shouldUseDark);
                    root.dataset.colorMode = colorMode;
                };

                applyColorMode();

                if (colorMode === 'system') {
                    if (typeof mediaQuery.addEventListener === 'function') {
                        mediaQuery.addEventListener('change', applyColorMode);
                    } else if (typeof mediaQuery.addListener === 'function') {
                        mediaQuery.addListener(applyColorMode);
                    }
                }
            })();
        </script>
    </head>
    <body class="font-sans antialiased">
        <div class="tenant-shell min-h-screen" x-data="{ sidebarOpen: false }">
            <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 bg-gray-950/60 lg:hidden" x-on:click="sidebarOpen = false" x-cloak></div>

            <aside :class="sidebarOpen ? 'translate-x-0' : '{{ $mobileClosedSidebarClass }}'" class="fixed inset-y-0 {{ $mobileSidebarPositionClass }} {{ $desktopSidebarPositionClass }} {{ $floatingDesktopPositionClass }} z-50 w-72 {{ $desktopSidebarWidthClass }} flex flex-col {{ $sidebarSurfaceClasses }} transform transition-transform duration-300 ease-in-out lg:translate-x-0">
                <div class="flex h-16 items-center justify-between border-b border-gray-200 px-6 dark:border-slate-800">
                    <a href="{{ route('tenant.dashboard') }}" class="flex min-w-0 items-center gap-2">
                        @if ($logoVisible)
                            <img src="{{ $logoUrl }}" alt="Logo" class="h-8 w-8 flex-shrink-0 rounded-xl object-contain">
                        @endif
                        <span class="tenant-wordmark tenant-wordmark-sidebar truncate {{ $navLabelVisibilityClass }}">
                            <span>Laundry</span><span class="tenant-wordmark-accent">Track</span>
                        </span>
                    </a>
                    <button x-on:click="sidebarOpen = false" class="text-gray-400 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-100 lg:hidden">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-4">
                    <a href="{{ route('tenant.dashboard') }}" title="Dashboard" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('tenant.dashboard*') ? $activeNavClass : $inactiveNavClass }}">
                        <svg class="{{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                        <span class="{{ $navLabelVisibilityClass }}">Dashboard</span>
                    </a>

                    @auth
                        @if ($currentUser->isOwner() || $currentUser->isStaff())
                            <a href="{{ route('tenant.customers.index') }}" title="Customers" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('tenant.customers*') ? $activeNavClass : $inactiveNavClass }}">
                                <svg class="{{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                <span class="{{ $navLabelVisibilityClass }}">Customers</span>
                            </a>

                            <a href="{{ route('tenant.orders.index') }}" title="Orders" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('tenant.orders*') ? $activeNavClass : $inactiveNavClass }}">
                                <svg class="{{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                <span class="{{ $navLabelVisibilityClass }}">Orders</span>
                            </a>
                        @endif

                        @if ($currentUser->isOwner())
                            <a href="{{ route('tenant.services.index') }}" title="Services & Pricing" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('tenant.services*') ? $activeNavClass : $inactiveNavClass }}">
                                <svg class="{{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" /></svg>
                                <span class="{{ $navLabelVisibilityClass }}">Services & Pricing</span>
                            </a>

                            <a href="{{ route('tenant.staff.index') }}" title="Staff" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('tenant.staff*') ? $activeNavClass : $inactiveNavClass }}">
                                <svg class="{{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                                <span class="{{ $navLabelVisibilityClass }}">Staff</span>
                            </a>

                            @if (tenant()->hasFeature('expense_tracking'))
                                <a href="{{ route('tenant.expenses.index') }}" title="Expenses" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('tenant.expenses*') ? $activeNavClass : $inactiveNavClass }}">
                                    <svg class="{{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 3.071-.879 4.242 0M9.75 11.25c.386 0 .75.039 1.102.117m7.5-6.817A3.375 3.375 0 0015 2.25h-7.5A3.375 3.375 0 003.75 5.25m15 6V5.25A3.375 3.375 0 0015 1.5h-7.5A3.375 3.375 0 003.75 5.25v13.5A3.375 3.375 0 007.5 22.5h7.5a3.375 3.375 0 003.75-3.75V8.25m0 0H9" /></svg>
                                    <span class="{{ $navLabelVisibilityClass }}">Expenses</span>
                                </a>
                            @endif

                            @if (tenant()->hasFeature('reports'))
                                <a href="{{ route('tenant.reports.index') }}" title="Reports" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('tenant.reports*') ? $activeNavClass : $inactiveNavClass }}">
                                    <svg class="{{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                                    <span class="{{ $navLabelVisibilityClass }}">Reports</span>
                                </a>
                            @endif

                            <div class="mt-2 border-t border-gray-200 pt-2 dark:border-slate-800"></div>

                            <a href="{{ route('tenant.subscription') }}" title="Subscription" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('tenant.subscription*') ? $activeNavClass : $inactiveNavClass }}">
                                <svg class="{{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                                <span class="{{ $navLabelVisibilityClass }}">Subscription</span>
                            </a>
                        @endif

                        <a href="{{ route('tenant.settings.profile') }}" title="Settings" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('tenant.settings.*') ? $activeNavClass : $inactiveNavClass }}">
                            <svg class="{{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span class="{{ $navLabelVisibilityClass }}">Settings</span>
                        </a>

                        @if ($currentUser->isCustomer() && tenant()->hasFeature('customer_portal'))
                            <a href="{{ route('tenant.portal.index') }}" title="My Orders" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $navAlignmentClass }} {{ request()->routeIs('tenant.portal*') ? $activeNavClass : $inactiveNavClass }}">
                                <svg class="{{ $iconSpacingClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                <span class="{{ $navLabelVisibilityClass }}">My Orders</span>
                            </a>
                        @endif
                    @endauth
                </nav>

                <div class="border-t border-gray-200 px-4 py-4 dark:border-slate-800">
                    @auth
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $theme['avatar_bg'] }}">
                                    <span class="text-sm font-medium {{ $theme['avatar_text'] }}">{{ substr($currentUser->name, 0, 1) }}</span>
                                </div>
                            </div>
                            <div class="ml-3 min-w-0 flex-1 {{ $navLabelVisibilityClass }}">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">{{ $currentUser->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">{{ ucfirst($currentUser->role) }}</p>
                            </div>
                            <form method="POST" action="{{ route('tenant.logout') }}">
                                @csrf
                                <button type="submit" class="text-gray-400 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-100" title="Logout">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </aside>

            <div class="{{ $contentOffsetClass }}">
                <div class="{{ $topbarWrapperClass }}">
                    <div class="{{ $topbarSurfaceClass }}">
                        <div class="flex items-center gap-4">
                            <button x-on:click="sidebarOpen = true" class="-m-2.5 p-2.5 text-gray-700 dark:text-slate-100 lg:hidden">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                            </button>

                            @if ($logoVisible)
                                <img src="{{ $logoUrl }}" alt="Logo" class="h-9 w-9 rounded-xl object-contain">
                            @endif

                            <div class="min-w-0 flex-1">
                                <p class="tenant-wordmark tenant-wordmark-topbar">
                                    <span>Laundry</span><span class="tenant-wordmark-accent">Track</span>
                                </p>
                                @isset($header)
                                    <div class="mt-1 min-w-0">
                                        {{ $header }}
                                    </div>
                                @else
                                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-slate-100">{{ $shopName }}</p>
                                @endisset
                            </div>

                            @auth
                                <div class="hidden items-center gap-3 sm:flex">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full {{ $theme['avatar_bg'] }}">
                                        <span class="text-sm font-medium {{ $theme['avatar_text'] }}">{{ substr($currentUser->name, 0, 1) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">{{ $currentUser->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">{{ ucfirst($currentUser->role) }}</p>
                                    </div>
                                </div>
                            @endauth
                        </div>
                    </div>
                </div>

                <main class="px-4 pb-6 sm:px-6 lg:px-8">
                    @if (session('success'))
                        <div class="tenant-alert mb-4 border border-green-200 bg-green-50 p-4 text-green-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                            <p class="text-sm font-medium">{{ session('success') }}</p>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
