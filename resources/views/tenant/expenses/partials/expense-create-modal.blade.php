<x-modal name="expense-create-modal" :show="$showExpenseCreateModal" maxWidth="2xl" focusable>
    <form method="POST" action="{{ route('tenant.expenses.store') }}" class="p-6">
        @csrf
        <input type="hidden" name="form_context" value="expense-create">

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Expenses</p>
                <h3 class="mt-2 text-lg font-semibold text-gray-900">Record New Expense</h3>
                <p class="mt-1 text-sm text-gray-500">Track daily and monthly operating costs.</p>
            </div>

            <button type="button" x-on:click="$dispatch('close')" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                <select name="category" required class="block w-full rounded-md {{ $errors->has('category') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                    <option value="">- Select Category -</option>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
                <input type="text" name="description" required value="{{ old('description') }}" placeholder="e.g. Soap and detergent" class="block w-full rounded-md {{ $errors->has('description') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount (PHP) <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" required step="0.01" min="0.01" value="{{ old('amount') }}" placeholder="0.00" class="block w-full rounded-md {{ $errors->has('amount') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                    @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expense Date <span class="text-red-500">*</span></label>
                    <input type="date" name="expense_date" required value="{{ old('expense_date', today()->format('Y-m-d')) }}" class="block w-full rounded-md {{ $errors->has('expense_date') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                    @error('expense_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" placeholder="Optional details about this expense..." class="block w-full rounded-md {{ $errors->has('notes') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">{{ old('notes') }}</textarea>
                @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="$dispatch('close')" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Cancel
            </button>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2.5 text-sm font-medium text-white shadow-sm transition">
                Record Expense
            </button>
        </div>
    </form>
</x-modal>
