<x-tenant-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Expense</h2>
            <a href="{{ route('tenant.expenses.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to expenses</a>
        </div>
    </x-slot>

    @php $theme = app(\App\Services\ThemeService::class)->getTenantTheme(); @endphp

    <div class="max-w-2xl">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('tenant.expenses.update', $expense) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select name="category" required class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('category') border-red-300 @enderror">
                            <option value="">— Select Category —</option>
                            @foreach ($categories as $key => $label)
                                <option value="{{ $key }}" {{ old('category', $expense->category) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
                        <input type="text" name="description" required value="{{ old('description', $expense->description) }}" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-300 @enderror">
                        @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₱) <span class="text-red-500">*</span></label>
                        <input type="number" name="amount" required step="0.01" min="0.01" value="{{ old('amount', $expense->amount) }}" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('amount') border-red-300 @enderror">
                        @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Expense Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expense Date <span class="text-red-500">*</span></label>
                        <input type="date" name="expense_date" required value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('expense_date') border-red-300 @enderror">
                        @error('expense_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $expense->notes) }}</textarea>
                        @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="inline-flex items-center rounded-md {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-5 py-2 text-sm font-medium text-white shadow-sm transition">
                            Update Expense
                        </button>
                        <a href="{{ route('tenant.expenses.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-tenant-layout>
