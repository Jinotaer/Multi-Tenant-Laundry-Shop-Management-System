<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantMetric extends Model
{
    /**
     * Use central database connection.
     */
    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'database_size_mb',
        'storage_size_mb',
        'api_requests_count',
        'bandwidth_mb',
        'active_users_count',
        'orders_count',
        'customers_count',
        'recorded_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'database_size_mb' => 'decimal:2',
            'storage_size_mb' => 'decimal:2',
            'bandwidth_mb' => 'decimal:2',
            'api_requests_count' => 'integer',
            'active_users_count' => 'integer',
            'orders_count' => 'integer',
            'customers_count' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant this metric belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to filter metrics by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get metrics from the last N days.
     */
    public function scopeLastDays($query, int $days)
    {
        return $query->where('recorded_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get today's metrics.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('recorded_at', today());
    }

    /**
     * Scope to get this month's metrics.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('recorded_at', now()->month)
            ->whereYear('recorded_at', now()->year);
    }

    /**
     * Get the latest metric for a tenant.
     */
    public static function latestForTenant(string $tenantId): ?self
    {
        return static::where('tenant_id', $tenantId)
            ->latest('recorded_at')
            ->first();
    }

    /**
     * Get formatted storage size.
     */
    public function getFormattedStorageAttribute(): string
    {
        if ($this->storage_size_mb >= 1024) {
            return number_format($this->storage_size_mb / 1024, 2).' GB';
        }

        return number_format($this->storage_size_mb, 2).' MB';
    }

    /**
     * Get formatted bandwidth.
     */
    public function getFormattedBandwidthAttribute(): string
    {
        if ($this->bandwidth_mb >= 1024) {
            return number_format($this->bandwidth_mb / 1024, 2).' GB';
        }

        return number_format($this->bandwidth_mb, 2).' MB';
    }

    /**
     * Get formatted database size.
     */
    public function getFormattedDatabaseSizeAttribute(): string
    {
        if ($this->database_size_mb >= 1024) {
            return number_format($this->database_size_mb / 1024, 2).' GB';
        }

        return number_format($this->database_size_mb, 2).' MB';
    }

    /**
     * Get total storage (database + file storage).
     */
    public function getTotalStorageMbAttribute(): float
    {
        return (float) $this->database_size_mb + (float) $this->storage_size_mb;
    }

    /**
     * Get formatted total storage (database + file storage).
     */
    public function getFormattedTotalStorageAttribute(): string
    {
        $total = $this->total_storage_mb;
        if ($total >= 1024) {
            return number_format($total / 1024, 2).' GB';
        }

        return number_format($total, 2).' MB';
    }
}
