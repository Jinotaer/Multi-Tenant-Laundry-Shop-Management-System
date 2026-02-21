<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'service_id',
        'order_number',
        'status',
        'weight',
        'items',
        'total_amount',
        'payment_status',
        'paid_at',
        'due_date',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items' => 'array',
            'total_amount' => 'decimal:2',
            'weight' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * All available status labels.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            'received' => 'Received',
            'in_progress' => 'In Progress',
            'washing' => 'Washing',
            'drying' => 'Drying',
            'folding' => 'Folding',
            'ready' => 'Ready for Pickup',
            'claimed' => 'Claimed',
        ];
    }

    /**
     * Get statuses available for the current tenant's plan.
     * Starter: Received → In Progress → Ready → Claimed
     * Premium: Full extended workflow
     *
     * @return array<string, string>
     */
    public static function statusLabelsForPlan(): array
    {
        $all = self::statusLabels();
        $tenant = tenant();

        if ($tenant && $tenant->hasFeature('advanced_workflow')) {
            return $all;
        }

        // Starter basic workflow
        return [
            'received' => 'Received',
            'in_progress' => 'In Progress',
            'ready' => 'Ready for Pickup',
            'claimed' => 'Claimed',
        ];
    }

    /**
     * Status badge color classes.
     *
     * @return array<string, string>
     */
    public static function statusColors(): array
    {
        return [
            'received' => 'bg-yellow-100 text-yellow-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'washing' => 'bg-cyan-100 text-cyan-800',
            'drying' => 'bg-purple-100 text-purple-800',
            'folding' => 'bg-pink-100 text-pink-800',
            'ready' => 'bg-green-100 text-green-800',
            'claimed' => 'bg-gray-100 text-gray-800',
        ];
    }

    /**
     * Get the human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the badge color classes for the current status.
     */
    public function getStatusColorAttribute(): string
    {
        return self::statusColors()[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD-'.now()->format('Ymd');
        $last = self::where('order_number', 'like', $prefix.'%')->latest('id')->first();
        $seq = $last ? ((int) substr($last->order_number, -4)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if the order has been paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Get the customer that placed this order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the service for this order.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
