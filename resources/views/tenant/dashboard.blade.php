<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php $theme = tenant()->getThemePreset(); @endphp

    <div class="space-y-6">
        @if ($user->isOwner() || $user->isStaff())
            @foreach ($dashboardWidgets as $widgetKey)
                @includeIf("tenant.dashboard.widgets.{$widgetKey}")
            @endforeach
        @else
            <section class="tenant-panel overflow-hidden">
                <div class="p-6">
                    <p class="text-sm text-gray-600">As a customer, you'll be able to track your laundry orders here.</p>
                </div>
            </section>
        @endif
    </div>
</x-tenant-layout>
