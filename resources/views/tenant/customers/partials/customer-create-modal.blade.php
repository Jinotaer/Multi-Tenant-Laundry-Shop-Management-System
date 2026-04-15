<x-modal name="customer-create-modal" :show="$showCustomerCreateModal" maxWidth="2xl" focusable>
    <form method="POST" action="{{ route('tenant.customers.store') }}" class="p-6">
        @csrf
        <input type="hidden" name="form_context" value="customer-create">

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Customers</p>
                <h3 class="mt-2 text-lg font-semibold text-gray-900">Add Customer</h3>
                <p class="mt-1 text-sm text-gray-500">Create a customer record and let them set their password by email.</p>
            </div>

            <button type="button" x-on:click="$dispatch('close')" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-6 space-y-5">
            <div class="rounded-2xl border border-gray-200 bg-gray-50/90 px-4 py-4 dark:border-slate-700 dark:bg-slate-800/80">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl {{ $theme['avatar_bg'] }}">
                        <svg class="h-5 w-5 {{ $theme['avatar_text'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5A2.25 2.25 0 0 0 19.5 19.5v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75A2.25 2.25 0 0 0 6.75 21.75Z" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-slate-100">Customer login setup</p>
                        <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-slate-300">
                            If you provide an email address, the customer will receive an email to set their own password.
                            Leave the email blank if you only want to save their contact record for now.
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-gray-400 focus:ring-0 @error('name') border-red-300 @enderror"
                >
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone') }}"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-gray-400 focus:ring-0 @error('phone') border-red-300 @enderror"
                    >
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Email address</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-gray-400 focus:ring-0 @error('email') border-red-300 @enderror"
                    >
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="$dispatch('close')" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Cancel
            </button>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2.5 text-sm font-medium text-white shadow-sm transition">
                Save Customer
            </button>
        </div>
    </form>
</x-modal>
