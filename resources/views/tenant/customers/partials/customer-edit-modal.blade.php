@php
    $formContext = 'customer-edit-' . $customer->id;
    $isCurrentForm = old('form_context') === $formContext;
@endphp

<x-modal name="customer-edit-modal-{{ $customer->id }}" :show="$showCustomerEditModal" maxWidth="2xl" focusable>
    <form method="POST" action="{{ route('tenant.customers.update', $customer) }}" class="p-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="form_context" value="{{ $formContext }}">

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Customers</p>
                <h3 class="mt-2 text-lg font-semibold text-gray-900">Edit Customer</h3>
                <p class="mt-1 text-sm text-gray-500">Update customer details and notes.</p>
            </div>

            <button type="button" x-on:click="$dispatch('close')" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-6 space-y-5">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    name="name"
                    value="{{ $isCurrentForm ? old('name') : $customer->name }}"
                    required
                    class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-gray-400 focus:ring-0 {{ $isCurrentForm && $errors->has('name') ? 'border-red-300' : '' }}"
                >
                @if ($isCurrentForm)
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
                    <input
                        type="text"
                        name="phone"
                        value="{{ $isCurrentForm ? old('phone') : $customer->phone }}"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-gray-400 focus:ring-0 {{ $isCurrentForm && $errors->has('phone') ? 'border-red-300' : '' }}"
                    >
                    @if ($isCurrentForm)
                        @error('phone')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Email address</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ $isCurrentForm ? old('email') : $customer->email }}"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-gray-400 focus:ring-0 {{ $isCurrentForm && $errors->has('email') ? 'border-red-300' : '' }}"
                    >
                    @if ($isCurrentForm)
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Notes</label>
                <textarea
                    name="notes"
                    rows="3"
                    class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-gray-400 focus:ring-0 {{ $isCurrentForm && $errors->has('notes') ? 'border-red-300' : '' }}"
                >{{ $isCurrentForm ? old('notes') : $customer->notes }}</textarea>
                @if ($isCurrentForm)
                    @error('notes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="$dispatch('close')" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Cancel
            </button>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2.5 text-sm font-medium text-white shadow-sm transition">
                Update Customer
            </button>
        </div>
    </form>
</x-modal>
