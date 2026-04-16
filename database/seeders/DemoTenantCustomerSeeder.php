<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantRegistration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoTenantCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            SubscriptionPlanSeeder::class,
        ]);

        $tenantDefinitions = [
            [
                'id' => 'demo-north-laundry',
                'domain' => 'demo-north-laundry.localhost',
                'shop_name' => 'North Laundry Hub',
                'theme' => 'indigo',
                'owner_name' => 'North Owner',
                'owner_email' => '2301106754+north@student.buksu.edu.ph',
                'owner_password' => 'password123',
                'customer_password' => 'customer123',
                'customer_prefix' => 'north.customer',
                'customer_name_prefix' => 'North Customer',
                'customer_phone_prefix' => '09170001',
                'plan_slug' => 'starter',
                'is_paid' => false,
                'trial_days' => 30,
            ],
            [
                'id' => 'demo-south-laundry',
                'domain' => 'demo-south-laundry.localhost',
                'shop_name' => 'South Laundry Studio',
                'theme' => 'emerald',
                'owner_name' => 'South Owner',
                'owner_email' => '2301106754+south@student.buksu.edu.ph',
                'owner_password' => 'password123',
                'customer_password' => 'customer123',
                'customer_prefix' => 'south.customer',
                'customer_name_prefix' => 'South Customer',
                'customer_phone_prefix' => '09170002',
                'plan_slug' => 'premium',
                'is_paid' => true,
                'trial_days' => null,
            ],
        ];

        foreach ($tenantDefinitions as $tenantDefinition) {
            $this->seedTenant($tenantDefinition);
        }
    }

    /**
     * @param  array{
     *     id: string,
     *     domain: string,
     *     shop_name: string,
     *     theme: string,
     *     owner_name: string,
     *     owner_email: string,
     *     owner_password: string,
     *     customer_password: string,
     *     customer_prefix: string,
     *     customer_name_prefix: string,
     *     customer_phone_prefix: string,
     *     plan_slug: string,
     *     is_paid: bool,
     *     trial_days: int|null
     * }  $tenantDefinition
     */
    private function seedTenant(array $tenantDefinition): void
    {
        try {
            $plan = SubscriptionPlan::query()
                ->where('slug', $tenantDefinition['plan_slug'])
                ->first();

            if (!$plan) {
                $this->command?->warn("Plan '{$tenantDefinition['plan_slug']}' not found. Skipping tenant.");
                return;
            }

            $tenant = Tenant::query()->find($tenantDefinition['id']);

            if ($tenant !== null && ! $this->tenantDatabaseExists($tenant)) {
                $this->command?->warn("Tenant database missing. Recreating tenant: {$tenantDefinition['id']}");
                Tenant::withoutEvents(function () use ($tenant): void {
                    $tenant->domains()->delete();
                    $tenant->delete();
                });
                $tenant = null;
            }

            if ($tenant === null) {
                $this->dropOrphanedTenantDatabase($tenantDefinition['id']);
            }

            if ($tenant === null) {
                $tenant = Tenant::create([
                    'id' => $tenantDefinition['id'],
                    'theme' => $tenantDefinition['theme'],
                    'subscription_plan_id' => $plan->id,
                    'features' => $plan->features ?? [],
                    'trial_ends_at' => $tenantDefinition['trial_days'] !== null
                        ? now()->addDays($tenantDefinition['trial_days'])
                        : null,
                    'is_paid' => $tenantDefinition['is_paid'],
                    'data' => [
                        'shop_name' => $tenantDefinition['shop_name'],
                    ],
                ]);
            } else {
                $tenant->update([
                    'theme' => $tenantDefinition['theme'],
                    'subscription_plan_id' => $plan->id,
                    'features' => $plan->features ?? [],
                    'trial_ends_at' => $tenantDefinition['trial_days'] !== null
                        ? now()->addDays($tenantDefinition['trial_days'])
                        : null,
                    'is_paid' => $tenantDefinition['is_paid'],
                    'data' => [
                        'shop_name' => $tenantDefinition['shop_name'],
                    ],
                ]);
            }

            $tenant->domains()->firstOrCreate([
                'domain' => $tenantDefinition['domain'],
            ]);

            TenantRegistration::query()->updateOrCreate(
                ['subdomain' => $tenantDefinition['id']],
                [
                    'shop_name' => $tenantDefinition['shop_name'],
                    'owner_name' => $tenantDefinition['owner_name'],
                    'owner_email' => $tenantDefinition['owner_email'],
                    'owner_password' => $tenantDefinition['owner_password'],
                    'subscription_plan_id' => $plan->id,
                    'status' => 'approved',
                    'rejection_reason' => null,
                    'approved_at' => now(),
                    'rejected_at' => null,
                ],
            );

            $tenant->run(function () use ($tenantDefinition): void {
                User::query()->updateOrCreate(
                    ['email' => $tenantDefinition['owner_email']],
                    [
                        'name' => $tenantDefinition['owner_name'],
                        'password' => Hash::make($tenantDefinition['owner_password']),
                        'role' => 'owner',
                    ],
                );

                for ($index = 1; $index <= 10; $index++) {
                    $suffix = str_pad((string) $index, 2, '0', STR_PAD_LEFT);

                    Customer::query()->updateOrCreate(
                        ['email' => "{$tenantDefinition['customer_prefix']}{$suffix}@example.com"],
                        [
                            'name' => "{$tenantDefinition['customer_name_prefix']} {$suffix}",
                            'phone' => "{$tenantDefinition['customer_phone_prefix']}{$suffix}",
                            'password' => Hash::make($tenantDefinition['customer_password']),
                            'role' => 'customer',
                        ],
                    );
                }
            });

            $this->command?->info(
                "✓ Seeded {$tenantDefinition['shop_name']} ({$tenantDefinition['domain']}) with owner and 10 customers.",
            );
        } catch (\Exception $e) {
            $this->command?->error(
                "Failed to seed tenant {$tenantDefinition['id']}: {$e->getMessage()}"
            );
        }
    }

    private function dropOrphanedTenantDatabase(string $tenantId): void
    {
        try {
            $databaseName = config('tenancy.database.prefix').$tenantId.config('tenancy.database.suffix');
            DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");
        } catch (\Exception $e) {
            $this->command?->warn("Could not drop database: {$e->getMessage()}");
        }
    }

    private function tenantDatabaseExists(Tenant $tenant): bool
    {
        try {
            return $tenant->database()->manager()->databaseExists(
                $tenant->database()->getName(),
            );
        } catch (\Exception $e) {
            return false;
        }
    }
}
