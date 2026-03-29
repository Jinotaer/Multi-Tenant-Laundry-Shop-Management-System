<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Subscription Plan') }}
        </h2>
    </x-slot>

    <form method="POST" action="{{ route('admin.subscription-plans.store') }}">
        @csrf
        @include('admin.subscription-plans.partials.form', ['plan' => null, 'submitLabel' => 'Create Plan'])
    </form>
</x-admin-layout>
