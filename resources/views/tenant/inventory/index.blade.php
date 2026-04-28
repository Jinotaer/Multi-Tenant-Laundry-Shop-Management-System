<x-tenant-layout>
    @php
        $theme = app(\App\Services\ThemeService::class)->getTenantTheme();
        $currentUser = auth()->user();
        $canCreateInventory = $currentUser !== null && ($currentUser->isOwner() || $currentUser->hasPermission('inventory.create'));
        $canUpdateInventory = $currentUser !== null && ($currentUser->isOwner() || $currentUser->hasPermission('inventory.update'));
        $canDeleteInventory = $currentUser !== null && ($currentUser->isOwner() || $currentUser->hasPermission('inventory.delete'));
        $canAdjustInventory = $currentUser !== null && ($currentUser->isOwner() || $currentUser->hasPermission('inventory.adjust'));
        $inventoryBaseUrl = url('inventory');
        $adjustBaseUrl = $inventoryBaseUrl;
        $showCreateModal = ($errors->isNotEmpty() && old('form_context') === 'inventory-create') || request()->boolean('create');
        $showEditModal = $errors->isNotEmpty() && old('form_context') === 'inventory-edit';
        $editItemId = old('inventory_edit_id');
        $editFormAction = $editItemId ? url("inventory/{$editItemId}") : '#';
    @endphp

    <div class="tenant-page-stack space-y-4">
        <x-tenant-header title="Inventory Management" description="Track supplies and inventory levels.">
            @if ($canCreateInventory)
                <x-slot name="actions">
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'inventory-create-modal')"
                        class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm transition"
                        style="background: var(--tenant-theme-accent);">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add Item
                    </button>
                </x-slot>
            @endif
        </x-tenant-header>

        @if (session('success'))
            <div class="rounded-md bg-green-50 border border-green-200 p-4">
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-md bg-red-50 border border-red-200 p-4">
                <p class="text-sm text-red-800">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-500">Total Inventory Value</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">₱{{ number_format($totalInventoryValue, 2) }}</div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-500">Total Items</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">{{ $items->total() }}</div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-500">Low Stock Alerts</div>
                    <div class="mt-2 text-3xl font-bold {{ $lowStockItems->isNotEmpty() ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $lowStockItems->count() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Low Stock Alert --}}
        @if ($lowStockItems->isNotEmpty())
            <div class="rounded-md bg-yellow-50 border border-yellow-200 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-yellow-800">{{ $lowStockItems->count() }} item(s) are running low on stock:</p>
                        <ul class="mt-1 text-sm text-yellow-700 list-disc list-inside space-y-0.5">
                            @foreach ($lowStockItems as $lowItem)
                                <li>{{ $lowItem->name }} &mdash; {{ number_format($lowItem->quantity_on_hand, 2) }} {{ $lowItem->unit }} remaining (reorder at {{ number_format($lowItem->reorder_level, 2) }})</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Inventory Items Table --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            @if ($items->isEmpty())
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No inventory items yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Start by adding supplies and materials to track.</p>
                    @if ($canCreateInventory)
                        <div class="mt-6">
                            <button
                                type="button"
                                x-data
                                x-on:click="$dispatch('open-modal', 'inventory-create-modal')"
                                class="inline-flex items-center px-4 py-2 {{ $theme['primary_bg'] }} {{ $theme['primary_hover'] }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
                                Add First Item
                            </button>
                        </div>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reorder Level</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost / Unit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                @if ($canAdjustInventory || $canUpdateInventory || $canDeleteInventory)
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($items as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $item->name }}</div>
                                        @if ($item->sku)
                                            <div class="text-xs text-gray-500">SKU: {{ $item->sku }}</div>
                                        @endif
                                        @if ($item->description)
                                            <div class="text-xs text-gray-400 mt-0.5 max-w-xs truncate">{{ $item->description }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $theme['badge_bg'] }} {{ $theme['badge_text'] }}">
                                            {{ $item->category }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $item->isLowStock() ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ number_format($item->quantity_on_hand, 2) }} {{ $item->unit }}
                                        @if ($item->isLowStock())
                                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Low</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ number_format($item->reorder_level, 2) }} {{ $item->unit }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if ($item->cost_per_unit !== null)
                                            ₱{{ number_format($item->cost_per_unit, 2) }}
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if ($item->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                        @endif
                                    </td>
                                    @if ($canAdjustInventory || $canUpdateInventory || $canDeleteInventory)
                                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-3">
                                            @if ($canAdjustInventory)
                                                <button
                                                    type="button"
                                                    x-data
                                                    x-on:click="$dispatch('open-modal', 'inventory-adjust-modal'); $dispatch('inventory-adjust-open', { id: {{ $item->id }}, name: {{ Js::from($item->name) }}, qty: {{ (float) $item->quantity_on_hand }}, unit: {{ Js::from($item->unit) }} })"
                                                    class="text-blue-600 hover:text-blue-900">
                                                    Adjust
                                                </button>
                                            @endif
                                            @if ($canUpdateInventory)
                                                <button
                                                    type="button"
                                                    x-data
                                                    x-on:click="$dispatch('open-modal', 'inventory-edit-modal'); $dispatch('inventory-edit-open', { id: {{ $item->id }}, name: {{ Js::from($item->name) }}, sku: {{ Js::from($item->sku ?? '') }}, category: {{ Js::from($item->category) }}, unit: {{ Js::from($item->unit) }}, qty: {{ (float) $item->quantity_on_hand }}, reorder_level: {{ (float) $item->reorder_level }}, cost_per_unit: {{ $item->cost_per_unit !== null ? (float) $item->cost_per_unit : 'null' }}, description: {{ Js::from($item->description ?? '') }}, is_active: {{ $item->is_active ? 'true' : 'false' }} })"
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                    Edit
                                                </button>
                                            @endif
                                            @if ($canDeleteInventory)
                                                <form method="POST" action="{{ route('tenant.inventory.destroy', $item) }}" class="inline"
                                                    onsubmit="return confirm('Delete {{ addslashes($item->name) }}? This action cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="bg-gray-50 border-t border-gray-200 px-6 py-4">
                    {{ $items->links() }}
                </div>
            @endif
        </div>

        {{-- Recent Adjustments --}}
        @if ($recentAdjustments->isNotEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Recent Stock Adjustments</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($recentAdjustments as $adjustment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $adjustment->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $adjustment->inventoryItem?->name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if ($adjustment->adjustment_type === 'stock_in')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Stock In</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Stock Out</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ number_format($adjustment->quantity, 2) }}
                                        {{ $adjustment->inventoryItem?->unit ?? '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $adjustment->performed_by_name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $adjustment->notes ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Modals --}}
        @if ($canCreateInventory)
            @include('tenant.inventory.partials.create-modal', ['theme' => $theme, 'showCreateModal' => $showCreateModal])
        @endif
        @if ($canUpdateInventory)
            @include('tenant.inventory.partials.edit-modal', ['theme' => $theme, 'showEditModal' => $showEditModal, 'editFormAction' => $editFormAction, 'editItemId' => $editItemId, 'inventoryBaseUrl' => $inventoryBaseUrl])
        @endif
        @if ($canAdjustInventory)
            @include('tenant.inventory.partials.adjust-modal', ['theme' => $theme, 'adjustBaseUrl' => $adjustBaseUrl])
        @endif
    </div>
</x-tenant-layout>
