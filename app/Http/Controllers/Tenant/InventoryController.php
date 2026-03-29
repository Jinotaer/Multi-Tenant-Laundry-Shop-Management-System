<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\InventoryAdjustmentRequest;
use App\Http\Requests\Tenant\InventoryItemRequest;
use App\Models\InventoryAdjustment;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Display inventory items, low-stock alerts, and recent adjustments.
     */
    public function index(): View
    {
        $items = InventoryItem::query()
            ->latest()
            ->paginate(15);
        $lowStockItems = InventoryItem::query()
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->orderBy('quantity_on_hand')
            ->get();
        $recentAdjustments = InventoryAdjustment::query()
            ->with('inventoryItem')
            ->latest()
            ->limit(10)
            ->get();
        $totalInventoryValue = InventoryItem::query()->get()->sum(
            fn (InventoryItem $item): float => (float) $item->quantity_on_hand * (float) ($item->cost_per_unit ?? 0)
        );

        return view('tenant.inventory.index', compact('items', 'lowStockItems', 'recentAdjustments', 'totalInventoryValue'));
    }

    /**
     * Show the form for creating an inventory item.
     */
    public function create(): View
    {
        return view('tenant.inventory.create');
    }

    /**
     * Store a newly created inventory item.
     */
    public function store(InventoryItemRequest $request): RedirectResponse
    {
        InventoryItem::create($request->validated());

        return redirect()->route('tenant.inventory.index')
            ->with('success', 'Inventory item created successfully.');
    }

    /**
     * Show the form for editing an inventory item.
     */
    public function edit(InventoryItem $inventory): View
    {
        return view('tenant.inventory.edit', ['item' => $inventory]);
    }

    /**
     * Update an inventory item.
     */
    public function update(InventoryItemRequest $request, InventoryItem $inventory): RedirectResponse
    {
        $inventory->update($request->validated());

        return redirect()->route('tenant.inventory.index')
            ->with('success', 'Inventory item updated successfully.');
    }

    /**
     * Remove an inventory item.
     */
    public function destroy(InventoryItem $inventory): RedirectResponse
    {
        $inventory->delete();

        return redirect()->route('tenant.inventory.index')
            ->with('success', 'Inventory item deleted successfully.');
    }

    /**
     * Record a stock adjustment and update the on-hand quantity.
     */
    public function adjust(InventoryAdjustmentRequest $request, InventoryItem $inventory): RedirectResponse
    {
        $validated = $request->validated();
        $quantity = round((float) $validated['quantity'], 2);
        $isInbound = $validated['adjustment_type'] === 'stock_in';
        $updatedQuantity = $isInbound
            ? (float) $inventory->quantity_on_hand + $quantity
            : (float) $inventory->quantity_on_hand - $quantity;

        if (! $isInbound && $updatedQuantity < 0) {
            return back()->with('error', 'Stock out quantity cannot reduce inventory below zero.');
        }

        $inventory->update([
            'quantity_on_hand' => round(max($updatedQuantity, 0), 2),
        ]);

        InventoryAdjustment::create([
            'inventory_item_id' => $inventory->id,
            'adjustment_type' => $validated['adjustment_type'],
            'quantity' => $quantity,
            'notes' => $validated['notes'] ?? null,
            'performed_by_name' => auth()->user()->name,
        ]);

        return redirect()->route('tenant.inventory.index')
            ->with('success', 'Inventory levels updated successfully.');
    }
}
