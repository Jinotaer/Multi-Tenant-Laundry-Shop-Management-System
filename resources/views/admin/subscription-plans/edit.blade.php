<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Plan:') }} {{ $plan->name }}
            </h2>
            <a href="{{ route('admin.subscription-plans.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to plans</a>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.subscription-plans.update', $plan) }}">
        @csrf
        @method('PUT')
        @include('admin.subscription-plans.partials.form', ['plan' => $plan, 'submitLabel' => 'Update Plan'])
    </form>
</x-admin-layout>
