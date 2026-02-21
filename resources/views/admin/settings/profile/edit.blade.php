<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    @php $theme = app(\App\Services\ThemeService::class)->getAdminTheme(); @endphp

    <!-- Settings Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <a href="{{ route('admin.settings.profile') }}" class="border-b-2 px-1 pb-3 text-sm font-medium {{ request()->routeIs('admin.settings.profile*') || request()->routeIs('admin.settings.password') ? 'border-current ' . $theme['nav_active_text'] : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                Profile
            </a>
            <a href="{{ route('admin.settings.theme') }}" class="border-b-2 px-1 pb-3 text-sm font-medium {{ request()->routeIs('admin.settings.theme*') || request()->routeIs('admin.settings.logo*') ? 'border-current ' . $theme['nav_active_text'] : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                Theme
            </a>
        </nav>
    </div>

    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('admin.settings.profile.partials.update-profile-information-form')
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('admin.settings.profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
