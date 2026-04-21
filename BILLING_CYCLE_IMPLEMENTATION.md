# Billing Cycle Implementation Guide

## ✅ Completed Steps

1. ✅ Created migration: `2026_04_20_000001_add_billing_cycle_tracking_to_tenants.php`
2. ✅ Created migration: `2026_04_20_000002_create_subscription_history_table.php`
3. ✅ Created tenant migration: `2026_04_20_000001_add_billing_cycle_to_orders.php`
4. ✅ Created model: `App\Models\SubscriptionHistory.php`

## 📋 Remaining Steps

### Step 1: Run Migrations

```bash
# Run central migrations
php artisan migrate

# Run tenant migrations
php artisan tenants:migrate
```

### Step 2: Add Billing Cycle Methods to Tenant Model

Add these methods to `app/Models/Tenant.php`:

```php
// Add to casts array
protected function casts(): array
{
    return [
        // ... existing casts
        'current_cycle_start' => 'datetime',
        'current_cycle_end' => 'datetime',
        'pending_plan_scheduled_at' => 'datetime',
    ];
}

// Add to getCustomColumns()
public static function getCustomColumns(): array
{
    return [
        // ... existing columns
        'current_cycle_start',
        'current_cycle_end',
        'pending_plan_id',
        'pending_plan_scheduled_at',
    ];
}

// Add these new methods at the end of the class

/**
 * Get current billing cycle dates
 */
public function getCurrentCycle(): array
{
    return [
        'start' => $this->current_cycle_start,
        'end' => $this->current_cycle_end,
    ];
}

/**
 * Check if current billing cycle has ended
 */
public function isCycleExpired(): bool
{
    if (!$this->current_cycle_end) {
        return false;
    }
    return now()->greaterThan($this->current_cycle_end);
}

/**
 * Get days remaining in current cycle
 */
public function daysRemainingInCycle(): int
{
    if (!$this->current_cycle_end || $this->isCycleExpired()) {
        return 0;
    }
    
    return now()->diffInDays($this->current_cycle_end);
}

/**
 * Calculate order count for current billing cycle
 */
public function getCurrentCycleOrderCount(): int
{
    if (!$this->current_cycle_start || !$this->current_cycle_end) {
        return 0;
    }
    
    return \DB::connection('tenant')
        ->table('orders')
        ->where('billing_cycle_start', '>=', $this->current_cycle_start)
        ->where('billing_cycle_end', '<=', $this->current_cycle_end)
        ->count();
}

/**
 * Get order limit from current plan
 */
public function getOrderLimit(): ?int
{
    return $this->subscriptionPlan?->order_limit;
}

/**
 * Check if order limit exceeded
 */
public function hasExceededOrderLimit(): bool
{
    $limit = $this->getOrderLimit();
    
    if ($limit === null) {
        return false; // Unlimited
    }
    
    return $this->getCurrentCycleOrderCount() > $limit;
}

/**
 * Get usage percentage
 */
public function getOrderUsagePercentage(): float
{
    $limit = $this->getOrderLimit();
    
    if ($limit === null) {
        return 0;
    }
    
    $current = $this->getCurrentCycleOrderCount();
    
    return ($current / $limit) * 100;
}

/**
 * Get usage display string
 */
public function getOrderUsageDisplay(): string
{
    $current = $this->getCurrentCycleOrderCount();
    $limit = $this->getOrderLimit();
    
    if ($limit === null) {
        return "{$current} / Unlimited";
    }
    
    return "{$current} / {$limit}";
}

/**
 * Check if plan change is pending
 */
public function hasPendingPlanChange(): bool
{
    return $this->pending_plan_id !== null;
}

/**
 * Get pending plan details
 */
public function getPendingPlan(): ?SubscriptionPlan
{
    if (!$this->hasPendingPlanChange()) {
        return null;
    }
    
    return SubscriptionPlan::find($this->pending_plan_id);
}

/**
 * Schedule plan change for next cycle
 */
public function schedulePlanChange(int $newPlanId): void
{
    $this->update([
        'pending_plan_id' => $newPlanId,
        'pending_plan_scheduled_at' => $this->current_cycle_end,
    ]);
}

/**
 * Cancel pending plan change
 */
public function cancelPendingPlanChange(): void
{
    $this->update([
        'pending_plan_id' => null,
        'pending_plan_scheduled_at' => null,
    ]);
}

/**
 * Get pending plan relationship
 */
public function pendingPlan(): BelongsTo
{
    return $this->belongsTo(SubscriptionPlan::class, 'pending_plan_id');
}

/**
 * Get subscription history
 */
public function subscriptionHistory(): HasMany
{
    return $this->hasMany(SubscriptionHistory::class);
}
```

### Step 3: Create BillingCycleService

Create file: `app/Services/BillingCycleService.php`

```php
<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionHistory;
use Carbon\Carbon;

class BillingCycleService
{
    /**
     * Start new billing cycle for tenant
     */
    public function startNewCycle(Tenant $tenant): void
    {
        $plan = $tenant->subscriptionPlan;
        $cycleStart = now();
        $cycleEnd = $this->calculateCycleEnd($cycleStart, $plan->billing_cycle);

        $tenant->update([
            'current_cycle_start' => $cycleStart,
            'current_cycle_end' => $cycleEnd,
            'subscription_expires_at' => $cycleEnd,
        ]);
    }

    /**
     * Process billing cycle renewal
     */
    public function renewCycle(Tenant $tenant): void
    {
        // Check if there's a pending plan change
        if ($tenant->hasPendingPlanChange()) {
            $this->applyPendingPlanChange($tenant);
        } else {
            // Continue with same plan
            $this->continueWithCurrentPlan($tenant);
        }
    }

    /**
     * Apply pending plan change
     */
    protected function applyPendingPlanChange(Tenant $tenant): void
    {
        $oldPlan = $tenant->subscriptionPlan;
        $newPlan = $tenant->getPendingPlan();
        
        $cycleStart = $tenant->current_cycle_end->addSecond();
        $cycleEnd = $this->calculateCycleEnd($cycleStart, $newPlan->billing_cycle);

        // Update tenant with new plan
        $tenant->update([
            'subscription_plan_id' => $newPlan->id,
            'current_cycle_start' => $cycleStart,
            'current_cycle_end' => $cycleEnd,
            'subscription_expires_at' => $cycleEnd,
            'features' => $newPlan->features,
            'pending_plan_id' => null,
            'pending_plan_scheduled_at' => null,
        ]);

        // Log history
        SubscriptionHistory::create([
            'tenant_id' => $tenant->id,
            'old_plan_id' => $oldPlan->id,
            'new_plan_id' => $newPlan->id,
            'change_type' => $newPlan->price > $oldPlan->price ? 'upgrade' : 'downgrade',
            'cycle_start' => $cycleStart,
            'cycle_end' => $cycleEnd,
            'amount_paid' => $newPlan->price,
        ]);
    }

    /**
     * Continue with current plan
     */
    protected function continueWithCurrentPlan(Tenant $tenant): void
    {
        $plan = $tenant->subscriptionPlan;
        
        $cycleStart = $tenant->current_cycle_end->addSecond();
        $cycleEnd = $this->calculateCycleEnd($cycleStart, $plan->billing_cycle);

        $tenant->update([
            'current_cycle_start' => $cycleStart,
            'current_cycle_end' => $cycleEnd,
            'subscription_expires_at' => $cycleEnd,
        ]);

        // Log renewal
        SubscriptionHistory::create([
            'tenant_id' => $tenant->id,
            'old_plan_id' => $plan->id,
            'new_plan_id' => $plan->id,
            'change_type' => 'renewal',
            'cycle_start' => $cycleStart,
            'cycle_end' => $cycleEnd,
            'amount_paid' => $plan->price,
        ]);
    }

    /**
     * Calculate cycle end date
     */
    protected function calculateCycleEnd(Carbon $start, string $billingCycle): Carbon
    {
        return match ($billingCycle) {
            'yearly' => $start->copy()->addYear()->subSecond(),
            default => $start->copy()->addMonth()->subSecond(),
        };
    }

    /**
     * Check and process expired cycles (run via cron)
     */
    public function processExpiredCycles(): void
    {
        $expiredTenants = Tenant::where('current_cycle_end', '<=', now())
            ->where('is_enabled', true)
            ->get();

        foreach ($expiredTenants as $tenant) {
            $this->renewCycle($tenant);
        }
    }
}
```

### Step 4: Update OrderController to Track Billing Cycles

In `app/Http/Controllers/Tenant/OrderController.php`, update the `store()` method:

```php
public function store(OrderRequest $request): RedirectResponse
{
    if (! $this->canCreateOrder()) {
        return redirect()->route('tenant.orders.index')
            ->with('error', 'Monthly order limit reached for your current plan. Please upgrade to create more orders.');
    }

    $data = $request->validated();
    $data['order_number'] = Order::generateOrderNumber();
    $data['payment_status'] = 'unpaid';
    
    // ADD BILLING CYCLE TRACKING
    $tenant = tenant();
    $cycle = $tenant->getCurrentCycle();
    $data['billing_cycle_start'] = $cycle['start'];
    $data['billing_cycle_end'] = $cycle['end'];
    
    $data = $this->prepareOrderData($data);

    Order::create($data);

    return redirect()->route('tenant.orders.index')
        ->with('success', "Order {$data['order_number']} created successfully.");
}
```

### Step 5: Create Console Command for Billing Cycle Processing

Create file: `app/Console/Commands/ProcessBillingCycles.php`

```php
<?php

namespace App\Console\Commands;

use App\Services\BillingCycleService;
use Illuminate\Console\Command;

class ProcessBillingCycles extends Command
{
    protected $signature = 'billing:process-cycles';
    protected $description = 'Process expired billing cycles and apply plan changes';

    public function handle(BillingCycleService $service): int
    {
        $this->info('Processing expired billing cycles...');
        
        $service->processExpiredCycles();
        
        $this->info('Billing cycles processed successfully.');
        
        return 0;
    }
}
```

### Step 6: Schedule the Command

In `app/Console/Kernel.php`, add to schedule() method:

```php
protected function schedule(Schedule $schedule)
{
    // Run every hour to check for expired cycles
    $schedule->command('billing:process-cycles')->hourly();
}
```

### Step 7: Initialize Billing Cycles for Existing Tenants

Create a one-time command to initialize cycles:

```php
<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\BillingCycleService;
use Illuminate\Console\Command;

class InitializeBillingCycles extends Command
{
    protected $signature = 'billing:initialize';
    protected $description = 'Initialize billing cycles for existing tenants';

    public function handle(BillingCycleService $service): int
    {
        $tenants = Tenant::whereNull('current_cycle_start')->get();
        
        $this->info("Initializing billing cycles for {$tenants->count()} tenants...");
        
        foreach ($tenants as $tenant) {
            $service->startNewCycle($tenant);
            $this->info("Initialized cycle for tenant: {$tenant->id}");
        }
        
        $this->info('All billing cycles initialized successfully.');
        
        return 0;
    }
}
```

Run this once after deployment:
```bash
php artisan billing:initialize
```

### Step 8: Update Dashboard to Show Usage

See the file: `DASHBOARD_USAGE_WIDGET.md` for the complete widget code.

### Step 9: Update Subscription Change Flow

Modify `SubscriptionDowngradeController::checkout()` to schedule instead of immediate change:

```php
// Instead of immediate update, schedule the change
$tenant->schedulePlanChange($newPlan->id);

return redirect()->route('tenant.subscription')
    ->with('success', "Plan change scheduled. Your {$newPlan->name} will activate on " . $tenant->current_cycle_end->addDay()->format('M d, Y'));
```

## Testing Checklist

- [ ] Run migrations successfully
- [ ] Create a test order and verify billing_cycle_start/end are populated
- [ ] Check tenant dashboard shows correct order usage
- [ ] Schedule a plan change and verify it's pending
- [ ] Manually trigger billing cycle renewal: `php artisan billing:process-cycles`
- [ ] Verify plan change applied after cycle end
- [ ] Check subscription_history table has records
- [ ] Test order creation when limit exceeded (should still work with warning)
- [ ] Verify order count resets after new cycle starts

## API Endpoints to Add (Optional)

See `API_ENDPOINTS.md` for complete API implementation.

## Notes

- Orders are NEVER blocked, only warnings shown
- Plan changes take effect ONLY at cycle end
- All historical data preserved
- Order counts calculated dynamically from billing_cycle dates
- Cron job runs hourly to process cycle renewals
