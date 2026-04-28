<x-modal name="inventory-edit-modal" :show="$showEditModal" maxWidth="2xl" focusable>
    <div
        x-data="{
            formAction: {{ Js::from($editFormAction) }},
            editItemId: {{ Js::from((string) ($editItemId ?? '')) }},
            form: {{ Js::from([
                'name'             => old('name', ''),
                'sku'              => old('sku', ''),
                'category'         => old('category', ''),
                'unit'             => old('unit', ''),
                'quantity_on_hand' => old('quantity_on_hand', ''),
                'reorder_level'    => old('reorder_level', ''),
                'cost_per_unit'    => old('cost_per_unit', ''),
                'description'      => old('description', ''),
                'is_active'        => old('is_active', '1') !== '0',
            ]) }},
            open(data) {
                this.formAction = `{{ $inventoryBaseUrl }}/` + data.id;
                this.editItemId = String(data.id);
                this.form.name = data.name;
                this.form.sku = data.sku;
                this.form.category = data.category;
                this.form.unit = data.unit;
                this.form.quantity_on_hand = data.qty;
                this.form.reorder_level = data.reorder_level;
                this.form.cost_per_unit = data.cost_per_unit !== null ? String(data.cost_per_unit) : '';
                this.form.description = data.description;
                this.form.is_active = data.is_active;
            }
        }"
        x-on:inventory-edit-open.window="open($event.detail)">

        <form method="POST" :action="formAction" class="p-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="form_context" value="inventory-edit">
            <input type="hidden" name="inventory_edit_id" :value="editItemId">

            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Inventory</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900">Edit Inventory Item</h3>
                    <p class="mt-1 text-sm text-gray-500" x-text="form.name || 'Update item details.'"></p>
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
                    <input type="text" name="name" required x-model="form.name"
                        class="block w-full rounded-md {{ $errors->has('name') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                        <input type="text" name="sku" x-model="form.sku"
                            class="block w-full rounded-md {{ $errors->has('sku') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                        @error('sku') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <input type="text" name="category" required x-model="form.category"
                            class="block w-full rounded-md {{ $errors->has('category') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                        @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit of Measure <span class="text-red-500">*</span></label>
                    <input type="text" name="unit" required x-model="form.unit"
                        class="block w-full rounded-md {{ $errors->has('unit') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                    @error('unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity on Hand <span class="text-red-500">*</span></label>
                        <input type="number" name="quantity_on_hand" required step="0.01" min="0" x-model="form.quantity_on_hand"
                            class="block w-full rounded-md {{ $errors->has('quantity_on_hand') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                        @error('quantity_on_hand') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Level <span class="text-red-500">*</span></label>
                        <input type="number" name="reorder_level" required step="0.01" min="0" x-model="form.reorder_level"
                            class="block w-full rounded-md {{ $errors->has('reorder_level') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                        @error('reorder_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cost Per Unit (₱)</label>
                    <input type="number" name="cost_per_unit" step="0.01" min="0" x-model="form.cost_per_unit" placeholder="0.00"
                        class="block w-full rounded-md {{ $errors->has('cost_per_unit') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm">
                    @error('cost_per_unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="2" x-model="form.description"
                        class="block w-full rounded-md {{ $errors->has('description') ? 'border-red-300' : 'border-gray-300' }} shadow-sm text-sm"></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1"
                        :checked="form.is_active"
                        x-on:change="form.is_active = $event.target.checked"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm">
                    <label for="edit_is_active" class="text-sm font-medium text-gray-700">Active (visible in inventory)</label>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
                <button type="button" x-on:click="$dispatch('close')"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2.5 text-sm font-medium text-white shadow-sm transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</x-modal>
