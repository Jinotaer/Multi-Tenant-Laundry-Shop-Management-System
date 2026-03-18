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
     * Human-readable pricing mode label for the current tenant.
     */
    public static function pricingMode(): string
    {
        $tenant = tenant();

        if ($tenant && $tenant->hasFeature('advanced_pricing')) {
            return 'advanced';
        }

        if (! $tenant || $tenant->hasFeature('simple_pricing')) {
            return 'simple';
        }

        return 'unavailable';
    }

    /**
     * Descriptions shown for each pricing type in the UI.
     *
     * @return array<string, string>
     */
    public static function priceTypeDescriptions(): array
    {
        return [
            'per_kilo' => 'Charge by the recorded laundry weight.',
            'per_load' => 'Charge one fixed amount per machine load.',
            'per_piece' => 'Charge per clothing piece, with optional per-line overrides.',
            'flat' => 'Charge one flat amount for the service regardless of quantity.',
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
        return match (self::pricingMode()) {
            'advanced' => self::priceTypeLabels(),
            'simple' => ['per_kilo' => 'Per Kilo'],
            default => [],
        };
    }

    /**
     * Determine whether the service requires weight-based pricing.
     */
    public function requiresWeight(): bool
    {
        return $this->price_type === 'per_kilo';
    }

    /**
     * Determine whether the service uses line-item pricing.
     */
    public function usesItemizedPricing(): bool
    {
        return $this->price_type === 'per_piece';
    }

    /**
     * Normalize item entries before storing them on an order.
     *
     * @param  array<int, mixed>  $items
     * @return array<int, array{name: string, qty: int, price: float|null}>
     */
    public static function normalizeItemEntries(array $items): array
    {
        $normalized = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));
            $quantity = max((int) ($item['qty'] ?? 1), 1);
            $price = is_numeric($item['price'] ?? null) ? round((float) $item['price'], 2) : null;

            if ($name === '' && $price === null) {
                continue;
            }

            $normalized[] = [
                'name' => $name !== '' ? $name : 'Laundry Item '.($index + 1),
                'qty' => $quantity,
                'price' => $price,
            ];
        }

        return $normalized;
    }

    /**
     * Prepare item entries for storage on an order.
     *
     * @param  array<int, mixed>  $items
     * @return array<int, array{name: string, qty: int, price: float}>
     */
    public function prepareOrderItems(array $items): array
    {
        $prepared = [];

        foreach (self::normalizeItemEntries($items) as $item) {
            $price = $item['price'];

            if ($this->usesItemizedPricing() && $price === null) {
                $price = round((float) $this->price, 2);
            }

            $prepared[] = [
                'name' => $item['name'],
                'qty' => $item['qty'],
                'price' => round((float) ($price ?? 0), 2),
            ];
        }

        return $prepared;
    }

    /**
     * Sum a normalized set of item entries.
     *
     * @param  array<int, array{name?: string, qty?: int|float|string|null, price?: int|float|string|null}>  $items
     */
    public static function calculateItemizedTotal(array $items): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $quantity = max((int) ($item['qty'] ?? 1), 1);
            $price = round((float) ($item['price'] ?? 0), 2);

            $total += $quantity * $price;
        }

        return round($total, 2);
    }

    /**
     * Calculate the full order total for this service.
     *
     * @param  array<int, mixed>  $items
     */
    public function calculateOrderTotal(?float $weight = null, array $items = []): float
    {
        $preparedItems = $this->prepareOrderItems($items);
        $itemizedTotal = self::calculateItemizedTotal($preparedItems);

        $baseAmount = match ($this->price_type) {
            'per_kilo' => round((float) $this->price * max((float) ($weight ?? 0), 0), 2),
            'per_load', 'flat' => round((float) $this->price, 2),
            'per_piece' => 0.0,
            default => 0.0,
        };

        return round($baseAmount + $itemizedTotal, 2);
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
