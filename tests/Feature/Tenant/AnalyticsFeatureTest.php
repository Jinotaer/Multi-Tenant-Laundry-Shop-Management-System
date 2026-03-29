<?php

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $tenantKey = 'reports'.Str::lower(Str::random(8));

    $this->tenantDomain = $tenantKey.'.localhost';
    $this->tenant = Tenant::create([
        'id' => $tenantKey,
        'is_enabled' => true,
        'is_paid' => true,
        'features' => ['reports', 'expense_tracking'],
        'data' => ['shop_name' => 'Insight Laundry'],
    ]);

    $this->tenant->domains()->create([
        'domain' => $this->tenantDomain,
    ]);

    $this->tenant->run(function (): void {
        User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $customer = Customer::create([
            'name' => 'Report Customer',
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $service = Service::create([
            'name' => 'Wash and Fold',
            'price_type' => 'per_kilo',
            'price' => 70,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Order::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'order_number' => 'ORD-REPORT-0001',
            'status' => 'ready',
            'total_amount' => 350,
            'payment_status' => 'paid',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        Order::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'order_number' => 'ORD-REPORT-0002',
            'status' => 'in_progress',
            'total_amount' => 150,
            'payment_status' => 'unpaid',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        Expense::create([
            'category' => 'utilities',
            'description' => 'Weekly utility bill',
            'amount' => 90,
            'expense_date' => now()->subDay()->toDateString(),
        ]);
    });

    $this->tenantUrl = fn (string $path): string => "http://{$this->tenantDomain}{$path}";
    $this->loginOwner = function (): void {
        $this->post(($this->tenantUrl)('/login'), [
            'email' => 'owner@example.com',
            'password' => 'password',
        ])->assertRedirect(route('tenant.dashboard', absolute: false));
    };
});

afterEach(function () {
    tenancy()->end();

    if (isset($this->tenant) && $this->tenant->exists) {
        $this->tenant->delete();
    }
});

test('reports dashboard shows financial insight cards and export actions', function () {
    ($this->loginOwner)();

    $this->get(($this->tenantUrl)('/reports?period=month'))
        ->assertOk()
        ->assertSee('Export Excel')
        ->assertSee('Print / Save PDF')
        ->assertSee('Total Revenue')
        ->assertSee('Total Expenses')
        ->assertSee('Estimated Profit')
        ->assertSee('PHP 350.00')
        ->assertSee('PHP 90.00')
        ->assertSee('PHP 260.00')
        ->assertSee('Wash and Fold')
        ->assertSee('Recent Orders');
});

test('excel export returns csv metrics and insight sections', function () {
    ($this->loginOwner)();

    $response = $this->get(($this->tenantUrl)('/reports/export/excel?period=month'));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->headers->get('content-disposition'))->toContain('laundry-report-month-');
    expect($response->getContent())->toContain('"Metric","Value"');
    expect($response->getContent())->toContain('"Total Revenue","350.00"');
    expect($response->getContent())->toContain('"Total Expenses","90.00"');
    expect($response->getContent())->toContain('"Estimated Profit","260.00"');
    expect($response->getContent())->toContain('"Popular Services","Orders"');
    expect($response->getContent())->toContain('"Wash and Fold","2"');
    expect($response->getContent())->toContain('"Recent Orders",""');
    expect($response->getContent())->toContain('"ORD-REPORT-0001","Report Customer","Wash and Fold","Ready for Pickup","350.00","paid"');
});

test('pdf export returns a print ready business report', function () {
    ($this->loginOwner)();

    $this->get(($this->tenantUrl)('/reports/export/pdf?period=month'))
        ->assertOk()
        ->assertSee('Print / Save as PDF')
        ->assertSee('Laundry Business Report')
        ->assertSee('Insight Laundry')
        ->assertSee('Estimated Profit')
        ->assertSee('Orders by Status')
        ->assertSee('ORD-REPORT-0001');
});
