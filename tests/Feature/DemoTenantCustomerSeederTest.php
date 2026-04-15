<?php

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\TenantRegistration;
use App\Models\User;
use Database\Seeders\DemoTenantCustomerSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    tenancy()->end();

    Tenant::query()
        ->whereIn('id', ['demo-north-laundry', 'demo-south-laundry'])
        ->get()
        ->each
        ->delete();

    TenantRegistration::query()
        ->whereIn('subdomain', ['demo-north-laundry', 'demo-south-laundry'])
        ->delete();

    foreach (['demo-north-laundry', 'demo-south-laundry'] as $tenantId) {
        $databaseName = config('tenancy.database.prefix').$tenantId.config('tenancy.database.suffix');

        DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");
    }
});

afterEach(function () {
    tenancy()->end();

    Tenant::query()
        ->whereIn('id', ['demo-north-laundry', 'demo-south-laundry'])
        ->get()
        ->each
        ->delete();

    TenantRegistration::query()
        ->whereIn('subdomain', ['demo-north-laundry', 'demo-south-laundry'])
        ->delete();

    foreach (['demo-north-laundry', 'demo-south-laundry'] as $tenantId) {
        $databaseName = config('tenancy.database.prefix').$tenantId.config('tenancy.database.suffix');

        DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");
    }
});

test('demo tenant customer seeder creates two tenants with ten customers each', function () {
    $this->seed(DemoTenantCustomerSeeder::class);

    $tenants = Tenant::query()
        ->whereIn('id', ['demo-north-laundry', 'demo-south-laundry'])
        ->orderBy('id')
        ->get();

    expect($tenants)->toHaveCount(2);

    $expectedDomains = [
        'demo-north-laundry' => 'demo-north-laundry.localhost',
        'demo-south-laundry' => 'demo-south-laundry.localhost',
    ];

    foreach ($tenants as $tenant) {
        expect($tenant->domains()->where('domain', $expectedDomains[$tenant->id])->exists())->toBeTrue();

        $tenant->run(function () use ($tenant): void {
            expect(User::query()->where('role', 'owner')->count())->toBe(1);
            expect(Customer::query()->count())->toBe(10);
            expect(Customer::query()->where('role', 'customer')->count())->toBe(10);

            $firstCustomerEmail = match ($tenant->id) {
                'demo-north-laundry' => 'north.customer01@example.com',
                default => 'south.customer01@example.com',
            };

            expect(Customer::query()->where('email', $firstCustomerEmail)->exists())->toBeTrue();
        });
    }
});

test('demo tenant customer seeder creates approved registrations for admin shop listings', function () {
    $this->seed(DemoTenantCustomerSeeder::class);

    $registrations = TenantRegistration::query()
        ->whereIn('subdomain', ['demo-north-laundry', 'demo-south-laundry'])
        ->orderBy('subdomain')
        ->get();

    expect($registrations)->toHaveCount(2);
    expect($registrations->pluck('status')->all())->toBe(['approved', 'approved']);
    expect($registrations->pluck('subscription_plan_id')->filter()->count())->toBe(2);

    $approvedTenantIds = Tenant::query()
        ->approved()
        ->whereIn('id', ['demo-north-laundry', 'demo-south-laundry'])
        ->orderBy('id')
        ->pluck('id')
        ->all();

    expect($approvedTenantIds)->toBe([
        'demo-north-laundry',
        'demo-south-laundry',
    ]);
});

test('demo tenant customer seeder recreates a stale tenant row when the tenant database is missing', function () {
    Tenant::create([
        'id' => 'demo-north-laundry',
        'theme' => 'slate',
        'is_paid' => false,
        'data' => [
            'shop_name' => 'Stale Demo Shop',
        ],
    ]);

    expect(Tenant::query()->find('demo-north-laundry'))->not->toBeNull();

    $this->seed(DemoTenantCustomerSeeder::class);

    $tenant = Tenant::query()->findOrFail('demo-north-laundry');

    expect($tenant->theme)->toBe('indigo');
    expect($tenant->registration)->not->toBeNull();
    expect($tenant->registration->shop_name)->toBe('North Laundry Hub');
    expect($tenant->registration->status)->toBe('approved');

    $tenant->run(function (): void {
        expect(User::query()->where('role', 'owner')->count())->toBe(1);
        expect(Customer::query()->count())->toBe(10);
    });
});
