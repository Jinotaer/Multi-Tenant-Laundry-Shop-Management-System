<x-tenant-layout>
    @php $theme = app(\App\Services\ThemeService::class)->getTenantTheme(); @endphp

    <div class="tenant-page-stack max-w-2xl space-y-4">
        <x-tenant-header title="Add Inventory Item" description="Add a new supply or material to track.">
            <x-slot name="actions">
                <a href="{{ route('tenant.inventory.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to inventory</a>
            </x-slot>
        </x-tenant-header>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg max-w-2xl">
            <div class="p-6">
                <form method="POST" action="{{ route('tenant.inventory.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Item Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g. Laundry Detergent"
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm @error('name') border-red-300 @enderror">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                            <input type="text" name="sku" value="{{ old('sku') }}" placeholder="e.g. DET-001"
                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm @error('sku') border-red-300 @enderror">
                            @error('sku') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                            <input type="text" name="category" required value="{{ old('category') }}" placeholder="e.g. Chemicals"
                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm @error('category') border-red-300 @enderror">
                            @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit of Measure <span class="text-red-500">*</span></label>
                        <input type="text" name="unit" required value="{{ old('unit') }}" placeholder="e.g. kg, liters, pieces"
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm @error('unit') border-red-300 @enderror">
                        @error('unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Initial Quantity <span class="text-red-500">*</span></label>
                            <input type="number" name="quantity_on_hand" required step="0.01" min="0" value="{{ old('quantity_on_hand', 0) }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm @error('quantity_on_hand') border-red-300 @enderror">
                            @error('quantity_on_hand') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Level <span class="text-red-500">*</span></label>
                            <input type="number" name="reorder_level" required step="0.01" min="0" value="{{ old('reorder_level', 0) }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm @error('reorder_level') border-red-300 @enderror">
                            @error('reorder_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cost Per Unit (₱)</label>
                        <input type="number" name="cost_per_unit" step="0.01" min="0" value="{{ old('cost_per_unit') }}" placeholder="0.00"
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm @error('cost_per_unit') border-red-300 @enderror">
                        @error('cost_per_unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="3" placeholder="Optional notes about this item..."
                            class="block w-full rounded-md border-gray-300 shadow-sm text-sm @error('description') border-red-300 @enderror">{{ old('description') }}</textarea>
                        @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                            {{ old('is_active', true) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-indigo-600 shadow-sm">
                        <label for="is_active" class="text-sm font-medium text-gray-700">Active (visible in inventory)</label>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                            class="inline-flex items-center rounded-md {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-5 py-2 text-sm font-medium text-white shadow-sm transition">
                            Add Item
                        </button>
                        <a href="{{ route('tenant.inventory.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </div>
</x-tenant-layout>
