<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    @php
        $widgetViews = [
            'total_shops' => 'admin.dashboard.widgets.total_shops',
            'pending_registrations' => 'admin.dashboard.widgets.pending_registrations',
            'active_workspaces' => 'admin.dashboard.widgets.active_workspaces',
            'recent_shops' => 'admin.dashboard.widgets.recent_shops',
        ];
    @endphp

    <div class="space-y-6">
        @foreach ($dashboardWidgets as $widget)
            @include($widgetViews[$widget])
        @endforeach
    </div>
</x-admin-layout>
