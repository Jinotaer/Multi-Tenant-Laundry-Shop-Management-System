<?php

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->tenantDomain = 'auth-shop.localhost';

    $this->tenant = Tenant::create([
        'id' => 'auth-shop',
        'is_enabled' => true,
        'is_paid' => true,
        'features' => ['customer_portal'],
        'data' => ['shop_name' => 'Auth Shop'],
    ]);

    $this->tenant->domains()->create([
        'domain' => $this->tenantDomain,
    ]);
});

afterEach(function () {
    tenancy()->end();

    if (isset($this->tenant) && $this->tenant->exists) {
        $this->tenant->delete();
    }
});

test('owners can authenticate using the tenant login screen', function () {
    $this->tenant->run(function (): void {
        User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
    });

    $response = $this->post("http://{$this->tenantDomain}/login", [
        'email' => 'owner@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->assertAuthenticated('web');
    $this->assertGuest('customer');
});

test('customers can authenticate and reach protected tenant routes', function () {
    $customer = $this->tenant->run(function (): Customer {
        return Customer::create([
            'name' => 'Portal Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);
    });

    $response = $this->post("http://{$this->tenantDomain}/login", [
        'email' => 'customer@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->assertAuthenticatedAs($customer, 'customer');
    $this->assertGuest('web');

    $this->get("http://{$this->tenantDomain}/dashboard")
        ->assertRedirect(route('tenant.portal.index', absolute: false));
});
