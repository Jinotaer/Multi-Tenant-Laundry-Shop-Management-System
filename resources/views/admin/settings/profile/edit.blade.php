<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    @php $theme = app(\App\Services\ThemeService::class)->getAdminTheme(); @endphp

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
