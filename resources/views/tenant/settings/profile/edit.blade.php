<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    @php $theme = app(\App\Services\ThemeService::class)->getTenantTheme(); @endphp

    @include('tenant.settings.partials.tabs')

    <div class="space-y-6">
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
