<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'inventory_item_id',
        'adjustment_type',
        'quantity',
        'notes',
        'performed_by_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }

    /**
     * Get the adjusted inventory item.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Determine if the adjustment adds stock.
     */
    public function isInbound(): bool
    {
        return in_array($this->adjustment_type, ['restock', 'correction_in'], true);
    }
}
