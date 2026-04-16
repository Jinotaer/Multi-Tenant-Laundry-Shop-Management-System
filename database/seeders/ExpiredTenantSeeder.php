<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ExpiredTenantSeeder extends Seeder
{
    /**
     * Seed tenants with different expiration states for testing.
     */
    public function run(): void
    {
        $plan = SubscriptionPlan::where('name', 'Premium')->first();

        if (!$plan) {
            $this->command->error('Premium plan not found. Run DatabaseSeeder first.');
            return;
        }

        // 1. Expired tenant (past grace period) - BLOCKED
        $expiredTenant = Tenant::create([
            'id' => 'expired-shop',
            'subscription_plan_id' => $plan->id,
            'trial_ends_at' => now()->subDays(40),
            'subscription_expires_at' => now()->subDays(10), // 10 days ago
            'grace_period_days' => 7,
            'is_paid' => false,
            'is_enabled' => true,
        ]);
        $expiredTenant->domains()->create(['domain' => 'expired-shop.localhost']);

        // 2. Grace period tenant - ACCESSIBLE with warnings
        $graceTenant = Tenant::create([
            'id' => 'grace-shop',
            'subscription_plan_id' => $plan->id,
            'trial_ends_at' => now()->subDays(35),
            'subscription_expires_at' => now()->subDays(3), // 3 days ago
            'grace_period_days' => 7,
            'is_paid' => false,
            'is_enabled' => true,
        ]);
        $graceTenant->domains()->create(['domain' => 'grace-shop.localhost']);

        // 3. Active paid tenant - FULL ACCESS
        $activeTenant = Tenant::create([
            'id' => 'active-shop',
            'subscription_plan_id' => $plan->id,
            'trial_ends_at' => now()->subDays(10),
            'subscription_expires_at' => now()->addDays(20), // 20 days remaining
            'grace_period_days' => 7,
            'is_paid' => true,
            'is_enabled' => true,
        ]);
        $activeTenant->domains()->create(['domain' => 'active-shop.localhost']);

        $this->command->info('✓ Created test tenants:');
        $this->command->info('  1. expired-shop.localhost - BLOCKED (expired 10 days ago)');
        $this->command->info('  2. grace-shop.localhost - ACCESSIBLE (in grace period, 4 days left)');
        $this->command->info('  3. active-shop.localhost - FULL ACCESS (20 days remaining)');
    }
}
