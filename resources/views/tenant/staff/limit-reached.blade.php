<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Staff Limit Reached</h2>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="max-w-lg mx-auto text-center py-12">
        <svg class="mx-auto h-16 w-16 text-yellow-400 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-900">Staff Limit Reached</h3>
        <p class="mt-2 text-sm text-gray-500">You've reached the maximum number of staff members allowed on your current plan. Upgrade to add more.</p>
        <div class="mt-6 flex items-center justify-center gap-3">
            <a href="{{ route('tenant.subscription') }}"
                class="inline-flex items-center rounded-md {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-5 py-2 text-sm font-medium text-white shadow-sm transition">
                Upgrade Plan
            </a>
            <a href="{{ route('tenant.staff.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Back to Staff</a>
        </div>
    </div>
</x-tenant-layout>
