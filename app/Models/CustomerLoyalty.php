<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLoyalty extends Model
{
    use HasFactory;

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
        return match (true) {
            $spent >= 50000 => 'platinum',
            $spent >= 20000 => 'gold',
            $spent >= 10000 => 'silver',
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
}

