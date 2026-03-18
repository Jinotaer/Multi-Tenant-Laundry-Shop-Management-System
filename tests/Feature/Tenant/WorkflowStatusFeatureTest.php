<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    $tenantKey = 'workflow'.Str::lower(Str::random(8));

    $this->tenantDomain = $tenantKey.'.localhost';
    $this->tenant = Tenant::create([
        'id' => $tenantKey,
        'is_enabled' => true,
        'is_paid' => true,
        'features' => ['customer_portal'],
        'data' => ['shop_name' => 'Workflow Shop'],
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
            'name' => 'Portal Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $this->customerId = $customer->id;
    });
});

afterEach(function () {
    tenancy()->end();

    if (isset($this->tenant) && $this->tenant->exists) {
        $this->tenant->delete();
    }
});

test('basic workflow order page uses the current status sequence', function () {
    $order = $this->tenant->run(function (): Order {
        return Order::create([
            'customer_id' => $this->customerId,
            'order_number' => 'ORD-BASIC-0001',
            'status' => 'received',
            'total_amount' => 150,
            'payment_status' => 'unpaid',
        ]);
    });

    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'owner@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(tenantUrl($this->tenantDomain, "/orders/{$order->id}"))
        ->assertOk()
        ->assertSee('Start Processing')
        ->assertDontSee('Mark Delivered')
        ->assertDontSee('Start Washing');
});

test('advanced workflow order page exposes the extended status sequence', function () {
    $this->tenant->features = ['customer_portal', 'advanced_workflow'];
    $this->tenant->save();

    $order = $this->tenant->run(function (): Order {
        return Order::create([
            'customer_id' => $this->customerId,
            'order_number' => 'ORD-ADV-0001',
            'status' => 'in_progress',
            'total_amount' => 225,
            'payment_status' => 'unpaid',
        ]);
    });

    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'owner@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(tenantUrl($this->tenantDomain, "/orders/{$order->id}"))
        ->assertOk()
        ->assertSee('Move to Washing')
        ->assertDontSee('Mark Delivered');
});

test('customer portal excludes claimed orders from active orders and shows plan aware progress steps', function () {
    $this->tenant->features = ['customer_portal', 'advanced_workflow'];
    $this->tenant->save();

    $activeOrder = $this->tenant->run(function (): Order {
        return Order::create([
            'customer_id' => $this->customerId,
            'order_number' => 'ORD-PORTAL-0001',
            'status' => 'washing',
            'total_amount' => 300,
            'payment_status' => 'unpaid',
        ]);
    });

    $this->tenant->run(function (): void {
        Order::create([
            'customer_id' => $this->customerId,
            'order_number' => 'ORD-PORTAL-0002',
            'status' => 'claimed',
            'total_amount' => 180,
            'payment_status' => 'paid',
        ]);
    });

    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'customer@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $portalResponse = $this->get(tenantUrl($this->tenantDomain, '/portal'));

    $portalResponse
        ->assertOk()
        ->assertSee('ORD-PORTAL-0001')
        ->assertSee('ORD-PORTAL-0002')
        ->assertDontSee('No active orders.');

    $portalHtml = $portalResponse->getContent();

    expect(substr_count($portalHtml, 'ORD-PORTAL-0001'))->toBeGreaterThan(1);
    expect(substr_count($portalHtml, 'ORD-PORTAL-0002'))->toBe(1);

    $this->get(tenantUrl($this->tenantDomain, "/portal/{$activeOrder->id}"))
        ->assertOk()
        ->assertSee('Folding')
        ->assertSee('Claimed')
        ->assertDontSee('Delivered');
});

test('customers without the portal feature can still access their dashboard shell', function () {
    $this->tenant->features = [];
    $this->tenant->save();

    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'customer@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(tenantUrl($this->tenantDomain, '/dashboard'))
        ->assertOk()
        ->assertSeeText("As a customer, you'll be able to track your laundry orders here.", false);
});

test('overview stats dashboard card uses active orders instead of legacy pending orders', function () {
    $this->tenant->features = ['advanced_workflow'];
    $this->tenant->save();

    $this->tenant->run(function (): void {
        Order::create([
            'customer_id' => $this->customerId,
            'order_number' => 'ORD-DASH-0001',
            'status' => 'washing',
            'total_amount' => 120,
            'payment_status' => 'unpaid',
        ]);
    });

    $this->post(tenantUrl($this->tenantDomain, '/login'), [
        'email' => 'owner@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(tenantUrl($this->tenantDomain, '/dashboard'))
        ->assertOk()
        ->assertSee('Active Orders')
        ->assertDontSee('Pending');
});

function tenantUrl(string $domain, string $path): string
{
    return "http://{$domain}{$path}";
}
