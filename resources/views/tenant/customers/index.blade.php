<x-tenant-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Customers') }}
        </h2>
    </x-slot>

    @php
        $theme = tenant()->getThemePreset();
        $showCustomerCreateModal = ($errors->isNotEmpty() && old('form_context') === 'customer-create')
            || request()->boolean('create');
    @endphp

    <div class="space-y-4">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" class="max-w-sm flex-1">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803 7.5 7.5 0 0 0 16.803 15.803z" />
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by name, phone or email..."
                        class="w-full rounded-md border border-gray-300 bg-white py-2 pl-9 pr-4 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    >
                </div>
            </form>

            <div class="flex items-center gap-2">
                @if ($canCreateCustomer && $canAddCustomer)
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'customer-create-modal')"
                        class="inline-flex items-center gap-2 rounded-md {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2 text-sm font-medium text-white shadow-sm transition"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add Customer
                    </button>
                @elseif ($canCreateCustomer)
                    <span class="inline-flex items-center gap-1 rounded-md border border-yellow-200 bg-yellow-50 px-4 py-2 text-sm text-yellow-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        Customer limit reached -
                        <a href="{{ route('tenant.subscription') }}" class="font-medium underline">Upgrade Plan</a>
                    </span>
                @endif
            </div>
        </div>

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            @if ($customers->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="mb-4 h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    <p class="text-sm font-medium text-gray-500">No customers yet</p>
                    <p class="mt-1 text-xs text-gray-400">Add your first customer to get started.</p>

                    @if ($canCreateCustomer && $canAddCustomer)
                        <button
                            type="button"
                            x-data
                            x-on:click="$dispatch('open-modal', 'customer-create-modal')"
                            class="mt-4 inline-flex items-center gap-1 text-sm font-medium {{ $theme['nav_active_text'] }} hover:underline"
                        >
                            Add Customer ->
                        </button>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Orders</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Added</th>
                                <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($customers as $customer)
                                <tr class="transition hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <a href="{{ route('tenant.customers.show', $customer) }}" class="font-medium text-gray-900 hover:{{ $theme['nav_active_text'] }}">
                                            {{ $customer->name }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $customer->phone ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $customer->email ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $customer->orders_count }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-400">{{ $customer->created_at->format('M d, Y') }}</td>
                                    <td class="space-x-2 whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        @if ($canUpdateCustomer)
                                            <a href="{{ route('tenant.customers.edit', $customer) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                        @endif
                                        @if ($canDeleteCustomer)
                                            <form method="POST" action="{{ route('tenant.customers.destroy', $customer) }}" class="inline" onsubmit="return confirm('Delete this customer? Their orders will also be removed.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($customers->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $customers->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    @if ($canCreateCustomer && $canAddCustomer)
        @include('tenant.customers.partials.customer-create-modal', [
            'theme' => $theme,
            'showCustomerCreateModal' => $showCustomerCreateModal,
        ])
    @endif
</x-tenant-layout>
