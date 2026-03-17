<div class="mb-6 border-b border-gray-200 dark:border-slate-800">
    <nav class="-mb-px flex flex-wrap gap-6">
        <a href="{{ route('tenant.settings.profile') }}" class="border-b-2 px-1 pb-3 text-sm font-medium {{ request()->routeIs('tenant.settings.profile*') || request()->routeIs('tenant.settings.password') ? 'border-current ' . $theme['nav_active_text'] : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:text-slate-200' }}">
            Profile
        </a>
        <a href="{{ route('tenant.settings.theme') }}" class="border-b-2 px-1 pb-3 text-sm font-medium {{ request()->routeIs('tenant.settings.theme*') || request()->routeIs('tenant.settings.logo*') ? 'border-current ' . $theme['nav_active_text'] : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:text-slate-200' }}">
            Layout
        </a>
    </nav>
</div>
