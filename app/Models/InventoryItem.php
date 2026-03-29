<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'sku',
        'unit',
        'category',
        'description',
        'quantity_on_hand',
        'reorder_level',
        'cost_per_unit',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'cost_per_unit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the item's adjustment history.
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    /**
     * Determine whether the item is low on stock.
     */
    public function isLowStock(): bool
    {
        return (float) $this->quantity_on_hand <= (float) $this->reorder_level;
    }
}
