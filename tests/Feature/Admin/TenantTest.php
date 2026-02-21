<?php

use App\Models\Admin;
use App\Models\Tenant;
use App\Models\TenantRegistration;

beforeEach(function () {
    $this->admin = Admin::factory()->create();
});

it('shows only tenants with approved registration on index', function () {
    // create a pending registration and corresponding tenant manually
    $pending = TenantRegistration::create([
        'shop_name' => 'Pending Shop',
        'subdomain' => 'pending-shop',
        'owner_name' => 'Pending Owner',
        'owner_email' => 'pending@example.com',
        'owner_password' => 'password',
        'status' => 'pending',
    ]);

    Tenant::create([
        'id' => 'pending-shop',
        'data' => ['shop_name' => 'Pending Shop'],
        'is_enabled' => true,
        'features' => [],
        'subscription_plan_id' => null,
        'is_paid' => false,
        'trial_ends_at' => now()->addDays(30),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // create an approved registration and let approval logic create the tenant
    $approvedReg = TenantRegistration::create([
        'shop_name' => 'Approved Shop',
        'subdomain' => 'approved-shop',
        'owner_name' => 'Approved Owner',
        'owner_email' => 'approved@example.com',
        'owner_password' => 'password',
        'status' => 'pending',
    ]);

    // calling the controller action to generate the tenant and mark approved
    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.registrations.approve', $approvedReg))
        ->assertRedirect();

    // now visit the tenants index
    $response = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.tenants.index'))
        ->assertOk();

    $response->assertSee('approved-shop');
    $response->assertDontSee('pending-shop');
});
