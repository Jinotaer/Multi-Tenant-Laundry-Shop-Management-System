<x-tenant-layout>
    @php 
        $theme = app(\App\Services\ThemeService::class)->getTenantTheme();
        $user = auth()->user();
        $isOwner = method_exists($user, 'isOwner') && $user->isOwner();
    @endphp

    <div class="space-y-6">
        <x-tenant-header title="Settings" description="Manage your account and shop settings." />

        {{-- Settings Navigation Tabs --}}
        @if ($isOwner)
            <div class="bg-white shadow-sm rounded-2xl dark:bg-slate-800 p-2">
                <nav class="flex gap-2" aria-label="Settings">
                    <a href="{{ route('tenant.settings.profile') }}" 
                       class="flex-1 rounded-lg px-4 py-3 text-sm font-medium text-center transition {{ request()->routeIs('tenant.settings.profile') ? 'tenant-primary-action' : 'text-gray-600 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700' }}">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            <span>Profile</span>
                        </div>
                    </a>
                    @if (tenant()->hasFeature('online_payments'))
                        <a href="{{ route('tenant.settings.paymongo') }}" 
                           class="flex-1 rounded-lg px-4 py-3 text-sm font-medium text-center transition {{ request()->routeIs('tenant.settings.paymongo') ? 'tenant-primary-action' : 'text-gray-600 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700' }}">
                            <div class="flex items-center justify-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                                <span>Payment Settings</span>
                            </div>
                        </a>
                    @endif
                </nav>
            </div>
        @endif

        <div class="tenant-panel overflow-hidden">
            <div class="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('tenant.settings.profile.partials.update-profile-information-form')
                </div>
            </div>
        </div>

        <div class="tenant-panel overflow-hidden">
            <div class="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('tenant.settings.profile.partials.update-password-form')
                </div>
            </div>
        </div>

        <div class="tenant-panel overflow-hidden">
            <div class="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('tenant.settings.profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-tenant-layout>
