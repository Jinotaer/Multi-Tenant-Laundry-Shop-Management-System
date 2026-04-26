<x-admin-layout>
    <x-slot name="header">
        <x-admin-header title="Create Subscription Plan" description="Add a new platform pricing tier." />
    </x-slot>

    <form method="POST" action="{{ route('admin.subscription-plans.store') }}" class="admin-page-stack">
        @csrf
        @include('admin.subscription-plans.partials.form', ['plan' => null, 'submitLabel' => 'Create Plan'])
    </form>
</x-admin-layout>
