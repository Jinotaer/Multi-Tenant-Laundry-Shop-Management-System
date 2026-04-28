<x-modal name="inventory-adjust-modal" maxWidth="md" focusable>
    <div
        x-data="{ item: null }"
        x-on:inventory-adjust-open.window="item = $event.detail">
        <form method="POST"
            :action="item ? '{{ $adjustBaseUrl }}/' + item.id + '/adjust' : '#'"
            class="p-6">
            @csrf

            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Inventory</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900">Adjust Stock</h3>
                    <p class="mt-1 text-sm text-gray-500"
                        x-text="item ? item.name + ' · ' + item.qty + ' ' + item.unit + ' on hand' : ''"></p>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adjustment Type <span class="text-red-500">*</span></label>
                    <select name="adjustment_type" required
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="stock_in">Stock In (add to inventory)</option>
                        <option value="stock_out">Stock Out (remove from inventory)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" required step="0.01" min="0.01" placeholder="0.00"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3" placeholder="Reason for adjustment..."
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm"></textarea>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
                <button type="button" x-on:click="$dispatch('close')"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} px-4 py-2.5 text-sm font-medium text-white shadow-sm transition">
                    Save Adjustment
                </button>
            </div>
        </form>
    </div>
</x-modal>
