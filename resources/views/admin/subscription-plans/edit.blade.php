<x-admin-layout>
    <x-slot name="header">
        <x-admin-header title="Edit Plan: {{ $plan->name }}" description="Modify pricing plan details.">
            <x-slot name="actions">
                <a href="{{ route('admin.subscription-plans.index') }}" class="text-sm text-gray-600 hover:text-gray-900 font-semibold">&larr; Back to plans</a>
            </x-slot>
        </x-admin-header>
    </x-slot>

    <form method="POST" action="{{ route('admin.subscription-plans.update', $plan) }}">
        @csrf
        @method('PUT')
        @include('admin.subscription-plans.partials.form', ['plan' => $plan, 'submitLabel' => 'Update Plan'])
    </form>
</x-admin-layout>
