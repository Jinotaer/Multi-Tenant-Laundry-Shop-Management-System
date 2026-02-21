<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'staff_limit',
        'customer_limit',
        'order_limit',
        'features',
        'is_active',
        'is_default',
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
            'features' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get all tenants subscribed to this plan.
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'subscription_plan_id');
    }

    /**
     * Check if this plan is a free plan.
     */
    public function isFree(): bool
    {
        return $this->price <= 0;
    }

    /**
     * Check if the plan has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Get the formatted price display.
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }

        return '₱'.number_format((float) $this->price, 2).'/'.$this->billing_cycle;
    }

    /**
     * Get the display text for staff limit.
     */
    public function getStaffLimitDisplayAttribute(): string
    {
        return $this->staff_limit === 0 ? 'Unlimited' : (string) $this->staff_limit;
    }

    /**
     * Get the display text for customer limit.
     */
    public function getCustomerLimitDisplayAttribute(): string
    {
        return $this->customer_limit === null ? 'Unlimited' : (string) $this->customer_limit;
    }

    /**
     * Get the display text for order limit.
     */
    public function getOrderLimitDisplayAttribute(): string
    {
        return $this->order_limit === null ? 'Unlimited' : (string) $this->order_limit;
    }

    /**
     * Scope to get only active plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the default plan.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }
}
