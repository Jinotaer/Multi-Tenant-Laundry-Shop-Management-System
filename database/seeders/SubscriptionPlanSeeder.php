<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'description' => 'Free plan for solo laundry operators and home-based shops. Essential digital tracking without complexity.',
                'price' => 0,
                'billing_cycle' => 'monthly',
                'staff_limit' => 1,
                'customer_limit' => 50,
                'order_limit' => 100,
                'features' => [
                    'basic_tracking',
                    'simple_pricing',
                    'customer_portal',
                ],
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ]
        );

        SubscriptionPlan::updateOrCreate(
            ['slug' => 'premium'],
            [
                'name' => 'Premium',
                'description' => 'Full laundry shop management for growing businesses and multi-staff shops. Unlocks all system features.',
                'price' => 2500,
                'billing_cycle' => 'monthly',
                'staff_limit' => 0,
                'customer_limit' => null,
                'order_limit' => null,
                'features' => [
                    'basic_tracking',
                    'advanced_workflow',
                    'simple_pricing',
                    'advanced_pricing',
                    'customer_portal',
                    'notifications',
                    'reports',
                    'analytics_dashboard',
                    'expense_tracking',
                    'customer_loyalty',
                    'custom_branding',
                    'online_payments',
                    'sms_notifications',
                    'inventory_management',
                    'priority_support',
                ],
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ]
        );
    }
}
