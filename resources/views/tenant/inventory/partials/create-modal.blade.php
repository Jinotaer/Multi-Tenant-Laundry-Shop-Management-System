<x-modal name="inventory-create-modal" :show="$showCreateModal" maxWidth="2xl" focusable>
    <form method="POST" action="{{ route('tenant.inventory.store') }}" class="p-6">
        @csrf
        <input type="hidden" name="form_context" value="inventory-create">

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Inventory</p>
                <h3 class="mt-2 text-lg font-semibold text-gray-900">Add Inventory Item</h3>
                <p class="mt-1 text-sm text-gray-500">Add a new supply or material to track.</p>
            </div>
            <button type="button" x-on:click="$dispatch('close')"
                class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Item Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g. Laundry Detergent"
                    class="block w-full rounded-md {{ $errors->has('name') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku') }}" placeholder="e.g. DET-001"
                        class="block w-full rounded-md {{ $errors->has('sku') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                    @error('sku') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <input type="text" name="category" required value="{{ old('category') }}" placeholder="e.g. Chemicals"
                        class="block w-full rounded-md {{ $errors->has('category') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                    @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit of Measure <span class="text-red-500">*</span></label>
                <input type="text" name="unit" required value="{{ old('unit') }}" placeholder="e.g. kg, liters, pieces"
                    class="block w-full rounded-md {{ $errors->has('unit') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                @error('unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Initial Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity_on_hand" required step="0.01" min="0" value="{{ old('quantity_on_hand', 0) }}"
                        class="block w-full rounded-md {{ $errors->has('quantity_on_hand') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                    @error('quantity_on_hand') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Level <span class="text-red-500">*</span></label>
                    <input type="number" name="reorder_level" required step="0.01" min="0" value="{{ old('reorder_level', 0) }}"
                        class="block w-full rounded-md {{ $errors->has('reorder_level') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                    @error('reorder_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cost Per Unit (₱)</label>
                <input type="number" name="cost_per_unit" step="0.01" min="0" value="{{ old('cost_per_unit') }}" placeholder="0.00"
                    class="block w-full rounded-md {{ $errors->has('cost_per_unit') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                @error('cost_per_unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" placeholder="Optional notes about this item..."
                    class="block w-full rounded-md {{ $errors->has('description') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">{{ old('description') }}</textarea>
                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="create_is_active" value="1"
                    {{ old('is_active', '1') !== '0' ? 'checked' : '' }}
                    class="rounded border-gray-300 text-indigo-600 shadow-sm">
                <label for="create_is_active" class="text-sm font-medium text-gray-700">Active (visible in inventory)</label>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="$dispatch('close')"
                class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Cancel
            </button>
            <button type="submit"
                class="inline-flex items-center justify-center rounded-xl {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2.5 text-sm font-medium text-white shadow-sm transition">
                Add Item
            </button>
        </div>
    </form>
</x-modal>
