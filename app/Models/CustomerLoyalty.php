<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLoyalty extends Model
{
    use HasFactory;

    /**
     * Tier spending thresholds.
     *
     * @return array<string, int>
     */
    public static function tierThresholds(): array
    {
        return [
            'bronze' => 0,
            'silver' => 10000,
            'gold' => 20000,
            'platinum' => 50000,
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'points',
        'stamps',
        'tier',
        'lifetime_spent',
        'last_earned_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'stamps' => 'integer',
            'lifetime_spent' => 'decimal:2',
            'last_earned_at' => 'datetime',
        ];
    }

    /**
     * Relationship: Customer this loyalty record belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Calculate tier based on lifetime spending.
     */
    public static function calculateTier(float $spent): string
    {
        $thresholds = self::tierThresholds();

        return match (true) {
            $spent >= $thresholds['platinum'] => 'platinum',
            $spent >= $thresholds['gold'] => 'gold',
            $spent >= $thresholds['silver'] => 'silver',
            default => 'bronze',
        };
    }

    /**
     * Get tier label with description.
     */
    public static function tierLabels(): array
    {
        return [
            'bronze' => 'Bronze (₱0 - ₱9,999)',
            'silver' => 'Silver (₱10,000 - ₱19,999)',
            'gold' => 'Gold (₱20,000 - ₱49,999)',
            'platinum' => 'Platinum (₱50,000+)',
        ];
    }

    /**
     * Get rewards for current tier (points per ₱100).
     */
    public function getRewardMultiplier(): float
    {
        return match ($this->tier) {
            'platinum' => 1.5,
            'gold' => 1.25,
            'silver' => 1.1,
            default => 1.0,
        };
    }

    /**
     * Add points and update tier.
     */
    public function addPoints(int $points, float $orderAmount): void
    {
        $this->points += $points;
        $this->stamps += 1;
        $this->lifetime_spent += $orderAmount;
        $this->tier = self::calculateTier($this->lifetime_spent);
        $this->last_earned_at = now();
        $this->save();
    }

    /**
     * Redeem points for discount.
     */
    public function redeemPoints(int $pointsToRedeem): bool
    {
        if ($this->points < $pointsToRedeem) {
            return false;
        }

        $this->points -= $pointsToRedeem;
        $this->save();

        return true;
    }

    /**
     * Get reward amount in PHP from points (1 point = ₱1).
     */
    public function getRewardValue(): float
    {
        return (float) $this->points;
    }

    /**
     * Get the next loyalty tier, if any.
     */
    public function nextTier(): ?string
    {
        return match ($this->tier) {
            'bronze' => 'silver',
            'silver' => 'gold',
            'gold' => 'platinum',
            default => null,
        };
    }

    /**
     * Get the minimum lifetime spend required for the next tier.
     */
    public function nextTierThreshold(): ?int
    {
        $nextTier = $this->nextTier();

        if (! $nextTier) {
            return null;
        }

        return self::tierThresholds()[$nextTier] ?? null;
    }

    /**
     * Get the remaining spend required to reach the next tier.
     */
    public function spendingNeededForNextTier(): float
    {
        $threshold = $this->nextTierThreshold();

        if (! $threshold) {
            return 0;
        }

        return max($threshold - (float) $this->lifetime_spent, 0);
    }

    /**
     * Get progress percentage toward the next tier.
     */
    public function progressToNextTier(): int
    {
        $thresholds = self::tierThresholds();
        $currentThreshold = $thresholds[$this->tier] ?? 0;
        $nextThreshold = $this->nextTierThreshold();

        if (! $nextThreshold || $nextThreshold <= $currentThreshold) {
            return 100;
        }

        $progress = ((float) $this->lifetime_spent - $currentThreshold) / ($nextThreshold - $currentThreshold);

        return (int) round(max(min($progress, 1), 0) * 100);
    }
}
