<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionHistory extends Model
{
    /**
     * The database connection that should be used by the model.
     */
    protected $connection = 'central';

    /**
     * The table associated with the model.
     */
    protected $table = 'subscription_history';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'old_plan_id',
        'new_plan_id',
        'change_type',
        'cycle_start',
        'cycle_end',
        'amount_paid',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'cycle_start' => 'datetime',
            'cycle_end' => 'datetime',
            'amount_paid' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the history record.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the old subscription plan.
     */
    public function oldPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'old_plan_id');
    }

    /**
     * Get the new subscription plan.
     */
    public function newPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'new_plan_id');
    }
}
