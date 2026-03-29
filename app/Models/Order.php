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
        'loyalty_points_awarded',
        'loyalty_points_awarded_at',
        'loyalty_points_redeemed',
        'loyalty_discount_amount',
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
            'loyalty_points_awarded' => 'integer',
            'loyalty_points_awarded_at' => 'datetime',
            'loyalty_points_redeemed' => 'integer',
            'loyalty_discount_amount' => 'decimal:2',
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

        if (! $tenant || $tenant->hasFeature('basic_tracking')) {
            return [
                'received' => 'Received',
                'in_progress' => 'In Progress',
                'ready' => 'Ready for Pickup',
                'claimed' => 'Claimed',
            ];
        }

        return [
            'received' => 'Received',
            'in_progress' => 'In Progress',
            'ready' => 'Ready for Pickup',
            'claimed' => 'Claimed',
        ];
    }

    /**
     * Get the ordered workflow sequence for the current tenant's plan.
     *
     * @return array<int, string>
     */
    public static function statusSequenceForPlan(): array
    {
        return array_keys(self::statusLabelsForPlan());
    }

    /**
     * Get the terminal workflow status for the current tenant's plan.
     */
    public static function terminalStatusForPlan(): string
    {
        $sequence = self::statusSequenceForPlan();

        return $sequence[array_key_last($sequence)] ?? 'claimed';
    }

    /**
     * Get the in-process statuses for the current tenant's plan.
     *
     * @return array<int, string>
     */
    public static function activeProcessingStatusesForPlan(): array
    {
        $terminalStatus = self::terminalStatusForPlan();

        return array_values(array_filter(
            self::statusSequenceForPlan(),
            fn (string $status): bool => ! in_array($status, ['ready', $terminalStatus], true),
        ));
    }

    /**
     * Get the next available workflow action for the given status.
     *
     * @return array<string, string>
     */
    public static function nextStatusActionsForPlan(string $currentStatus): array
    {
        $sequence = self::statusSequenceForPlan();
        $currentIndex = array_search($currentStatus, $sequence, true);

        if (! is_int($currentIndex) || $currentIndex >= count($sequence) - 1) {
            return [];
        }

        $nextStatus = $sequence[$currentIndex + 1];

        return [$nextStatus => self::transitionLabelForStatus($nextStatus)];
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
     * Determine whether the order can be paid online.
     */
    public function canBePaidOnline(): bool
    {
        return ! $this->isPaid() && (float) $this->total_amount > 0;
    }

    /**
     * Get the current balance due for the order.
     */
    public function outstandingBalance(): float
    {
        return round((float) $this->total_amount, 2);
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

    /**
     * Get the CTA label for a workflow transition.
     */
    private static function transitionLabelForStatus(string $status): string
    {
        return match ($status) {
            'in_progress' => 'Start Processing',
            'washing' => 'Move to Washing',
            'drying' => 'Move to Drying',
            'folding' => 'Move to Folding',
            'ready' => 'Mark Ready for Pickup',
            'claimed' => 'Mark as Claimed',
            default => 'Move to '.(self::statusLabels()[$status] ?? ucfirst(str_replace('_', ' ', $status))),
        };
    }
}
