<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    @php $theme = app(\App\Services\ThemeService::class)->getTenantTheme(); @endphp

    <!-- Settings Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <a href="{{ route('tenant.settings.profile') }}" class="border-b-2 px-1 pb-3 text-sm font-medium {{ request()->routeIs('tenant.settings.profile*') || request()->routeIs('tenant.settings.password') ? 'border-current ' . $theme['nav_active_text'] : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                Profile
            </a>
            <a href="{{ route('tenant.settings.theme') }}" class="border-b-2 px-1 pb-3 text-sm font-medium {{ request()->routeIs('tenant.settings.theme*') || request()->routeIs('tenant.settings.logo*') ? 'border-current ' . $theme['nav_active_text'] : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                Theme
            </a>
        </nav>
    </div>

    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('tenant.settings.profile.partials.update-profile-information-form')
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('tenant.settings.profile.partials.update-password-form')
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('tenant.settings.profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-tenant-layout>
