<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    /**
     * Use central database connection.
     */
    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'invoice_number',
        'tenant_id',
        'subscription_plan_id',
        'payment_id',
        'billing_name',
        'billing_email',
        'billing_address',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'currency',
        'issue_date',
        'due_date',
        'paid_at',
        'status',
        'period_start',
        'period_end',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'issue_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    /**
     * Invoice statuses.
     */
    const STATUS_DRAFT = 'draft';

    const STATUS_ISSUED = 'issued';

    const STATUS_PAID = 'paid';

    const STATUS_OVERDUE = 'overdue';

    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the tenant that owns this invoice.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the subscription plan for this invoice.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get the payment for this invoice.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->format('Y');
        $month = now()->format('m');

        // Get the last invoice number for this month
        $lastInvoice = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastInvoice->invoice_number);
            $sequence = isset($parts[3]) ? (int) $parts[3] + 1 : 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Create an invoice from a payment.
     */
    public static function createFromPayment(Payment $payment): self
    {
        $tenant = $payment->tenant;
        $plan = $payment->subscriptionPlan;

        // Calculate period (1 month or 1 year based on billing cycle)
        $periodStart = now();
        $periodEnd = $plan?->billing_cycle === 'yearly'
            ? now()->addYear()
            : now()->addMonth();

        return static::create([
            'invoice_number' => static::generateInvoiceNumber(),
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan?->id,
            'payment_id' => $payment->id,
            'billing_name' => $tenant->registration?->owner_name ?? $payment->customer_name ?? 'N/A',
            'billing_email' => $tenant->registration?->owner_email ?? $payment->customer_email ?? 'N/A',
            'subtotal' => $payment->amount,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => $payment->amount,
            'currency' => $payment->currency ?? 'PHP',
            'issue_date' => now(),
            'due_date' => now(), // Already paid
            'paid_at' => $payment->paid_at ?? now(),
            'status' => self::STATUS_PAID,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);
    }

    /**
     * Scope for paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [self::STATUS_ISSUED, self::STATUS_OVERDUE]);
    }

    /**
     * Scope for overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->status === self::STATUS_OVERDUE) {
            return true;
        }

        return $this->status === self::STATUS_ISSUED && $this->due_date->isPast();
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark invoice as overdue.
     */
    public function markAsOverdue(): void
    {
        $this->update([
            'status' => self::STATUS_OVERDUE,
        ]);
    }

    /**
     * Get formatted total with currency.
     */
    public function getFormattedTotalAttribute(): string
    {
        $symbol = $this->currency === 'PHP' ? '₱' : $this->currency.' ';

        return $symbol.number_format($this->total, 2);
    }

    /**
     * Get formatted subtotal with currency.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        $symbol = $this->currency === 'PHP' ? '₱' : $this->currency.' ';

        return $symbol.number_format($this->subtotal, 2);
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'green',
            self::STATUS_ISSUED => 'blue',
            self::STATUS_OVERDUE => 'red',
            self::STATUS_DRAFT => 'gray',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get status display name.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Paid',
            self::STATUS_ISSUED => 'Issued',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($this->status),
        };
    }
}
