<?php

namespace App\Models;

use App\Services\TenantFeatureService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
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
            'layout_settings',
            'features',
            'logo_path',
            'subscription_plan_id',
            'trial_ends_at',
            'subscription_expires_at',
            'grace_period_days',
            'last_renewal_reminder_sent_at',
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
            'layout_settings' => 'array',
            'features' => 'array',
            'trial_ends_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'last_renewal_reminder_sent_at' => 'datetime',
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
        $user = auth('web')->user() ?? auth('customer')->user();
        $key = $this->theme ?? config('themes.default');

        if ($user !== null && (! method_exists($user, 'isOwner') || ! $user->isOwner())) {
            $key = data_get($user, 'layout_preferences.theme') ?? $key;
        }

        return $presets[$key] ?? $presets[config('themes.default')];
    }

    /**
     * Check if a feature flag is enabled for this tenant.
     */
    public function hasFeature(string $feature): bool
    {
        return app(TenantFeatureService::class)->hasFeature($this->features, $feature);
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

    /**
     * Get the tenant's latest paid subscription payment.
     */
    public function latestPaidSubscriptionPayment(): ?Payment
    {
        return $this->payments()
            ->where('payment_type', 'subscription')
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->latest('paid_at')
            ->first();
    }

    /**
     * Get the updates/versions for this tenant.
     */
    public function updates(): HasMany
    {
        return $this->hasMany(TenantUpdate::class);
    }

    /**
     * Get the current active version tag for this tenant.
     */
    public function currentVersion(): string
    {
        $currentUpdate = $this->updates()->where('is_current', true)->with('release')->first();

        return $currentUpdate ? $currentUpdate->release->version_tag : 'v1.0.0';
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

    /**
     * Get the next renewal date for an active paid subscription.
     */
    public function subscriptionRenewsAt(): ?Carbon
    {
        if (! $this->is_paid || ! $this->subscriptionPlan || $this->subscriptionPlan->isFree()) {
            return null;
        }

        $paidSubscription = $this->latestPaidSubscriptionPayment();

        if (! $paidSubscription?->paid_at) {
            return null;
        }

        return match ($this->subscriptionPlan->billing_cycle) {
            'yearly' => $paidSubscription->paid_at->copy()->addYear(),
            default => $paidSubscription->paid_at->copy()->addMonth(),
        };
    }

    /**
     * Get the number of days remaining before the next paid renewal date.
     */
    public function paidDaysRemaining(): int
    {
        $subscriptionRenewsAt = $this->subscriptionRenewsAt();

        if ($subscriptionRenewsAt === null || $subscriptionRenewsAt->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($subscriptionRenewsAt, false);
    }

    /**
     * Check if the subscription has expired (past expiration date).
     */
    public function isSubscriptionExpired(): bool
    {
        return $this->subscription_expires_at !== null && $this->subscription_expires_at->isPast();
    }

    /**
     * Check if tenant is within grace period after subscription expiration.
     */
    public function isInGracePeriod(): bool
    {
        if (!$this->isSubscriptionExpired() || $this->is_paid) {
            return false;
        }

        $graceEndsAt = $this->subscription_expires_at->copy()->addDays($this->grace_period_days ?? 7);
        return now()->isBefore($graceEndsAt);
    }

    /**
     * Get the date when grace period ends.
     */
    public function graceEndsAt(): ?Carbon
    {
        if (!$this->subscription_expires_at) {
            return null;
        }

        return $this->subscription_expires_at->copy()->addDays($this->grace_period_days ?? 7);
    }

    /**
     * Get days remaining in grace period.
     */
    public function graceDaysRemaining(): int
    {
        $graceEndsAt = $this->graceEndsAt();

        if (!$graceEndsAt || $graceEndsAt->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($graceEndsAt, false);
    }

    /**
     * Check if tenant needs to renew subscription.
     */
    public function needsRenewal(): bool
    {
        return !$this->is_paid && ($this->isSubscriptionExpired() || $this->isInGracePeriod());
    }
}
