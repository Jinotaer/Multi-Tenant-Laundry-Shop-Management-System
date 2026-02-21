<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'price_type',
        'price',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Human-readable price type labels.
     *
     * @return array<string, string>
     */
    public static function priceTypeLabels(): array
    {
        return [
            'per_kilo' => 'Per Kilo',
            'per_load' => 'Per Load',
            'per_piece' => 'Per Piece',
            'flat' => 'Flat Rate',
        ];
    }

    /**
     * Get available price types for the current tenant's plan.
     * Starter: per_kilo only
     * Premium: all types
     *
     * @return array<string, string>
     */
    public static function availablePriceTypes(): array
    {
        $all = self::priceTypeLabels();
        $tenant = tenant();

        if ($tenant && $tenant->hasFeature('advanced_pricing')) {
            return $all;
        }

        // Starter: only per_kilo
        return ['per_kilo' => 'Per Kilo'];
    }

    /**
     * Get the formatted price display (e.g. "₱50.00/kg").
     */
    public function getFormattedPriceAttribute(): string
    {
        $suffix = match ($this->price_type) {
            'per_kilo' => '/kg',
            'per_load' => '/load',
            'per_piece' => '/pc',
            'flat' => '',
            default => '',
        };

        return '₱'.number_format((float) $this->price, 2).$suffix;
    }

    /**
     * Scope to only active services.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get orders using this service.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
