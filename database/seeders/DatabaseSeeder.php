<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            Sample1Seeder::class,
            AdminUserSeeder::class,
            SubscriptionPlanSeeder::class,
            DemoTenantCustomerSeeder::class,
            RoleSeeder::class,
            SimpleBillingTestSeeder::class,
           
        ]);
    }
}
