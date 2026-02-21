<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_enabled' => true,
        'theme' => 'indigo',
    ];

    /**
     * Define the actual database columns (not stored in `data` JSON).
     *
     * @return array<int, string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'is_enabled',
            'theme',
            'features',
            'logo_path',
            'subscription_plan_id',
            'trial_ends_at',
            'is_paid',
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_paid' => 'boolean',
            'features' => 'array',
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the active theme preset for this tenant.
     *
     * @return array<string, string>
     */
    public function getThemePreset(): array
    {
        $presets = config('themes.presets');
        $key = $this->theme ?? config('themes.default');

        return $presets[$key] ?? $presets[config('themes.default')];
    }

    /**
     * Check if a feature flag is enabled for this tenant.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Get the registration that created this tenant.
     */
    public function registration(): HasOne
    {
        return $this->hasOne(TenantRegistration::class, 'subdomain', 'id');
    }

    /**
     * Scope a query to only include tenants whose registration was approved.
     */
    public function scopeApproved(
        \Illuminate\Database\Eloquent\Builder $query
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->whereHas('registration', fn ($q) => $q->where('status', 'approved'));
    }

    /**
     * Get the subscription plan for this tenant.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get the payments for this tenant.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    public function isDisabled(): bool
    {
        return ! $this->is_enabled;
    }

    /**
     * Check if the tenant is currently on an active trial.
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the trial has expired.
     */
    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isPast();
    }

    /**
     * Check if the tenant has an active subscription (paid or on trial).
     */
    public function hasActiveSubscription(): bool
    {
        return $this->is_paid || $this->isOnTrial();
    }

    /**
     * Get the number of days remaining in the trial.
     */
    public function trialDaysRemaining(): int
    {
        if (! $this->trial_ends_at || $this->trial_ends_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->trial_ends_at, false);
    }
}
