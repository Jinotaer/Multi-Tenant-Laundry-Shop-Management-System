<?php

use App\Models\Admin;
use App\Models\TenantRegistration;

beforeEach(function () {
    $this->admin = Admin::factory()->create();
});

it('shows pending registration count in admin navigation', function () {
    TenantRegistration::create([
        'shop_name' => 'Count Shop',
        'subdomain' => 'count-shop',
        'owner_name' => 'Count Owner',
        'owner_email' => 'count@example.com',
        'owner_password' => 'password',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk();

    $response->assertSee('>1<', false);
});

it('does not render badge when there are no pending registrations', function () {
    $response = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk();

    $response->assertDontSee('bg-yellow-400');
});
