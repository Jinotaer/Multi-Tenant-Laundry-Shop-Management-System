<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /**
     * The database connection that should be used by the model.
     *
     * Payments live in the central database, not tenant databases.
     *
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'payment_type',
        'tenant_order_id',
        'paymongo_checkout_id',
        'paymongo_payment_id',
        'checkout_url',
        'amount',
        'currency',
        'status',
        'payment_method',
        'description',
        'customer_name',
        'customer_email',
        'metadata',
        'paid_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant this payment belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the subscription plan this payment is for.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Check if the payment is completed.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if the payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if this payment is for a subscription.
     */
    public function isSubscriptionPayment(): bool
    {
        return $this->payment_type === 'subscription';
    }

    /**
     * Check if this payment is for an order.
     */
    public function isOrderPayment(): bool
    {
        return $this->payment_type === 'order';
    }
}
