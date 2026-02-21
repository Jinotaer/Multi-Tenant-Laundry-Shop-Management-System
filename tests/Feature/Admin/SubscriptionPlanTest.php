<?php

use App\Models\Admin;
use App\Models\SubscriptionPlan;

beforeEach(function () {
    $this->admin = Admin::factory()->create();
});

test('guests cannot access subscription plans', function () {
    $this->get(route('admin.subscription-plans.index'))
        ->assertRedirect(route('admin.login'));
});

test('admin can view subscription plans index', function () {
    SubscriptionPlan::factory()->count(3)->create();

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.subscription-plans.index'))
        ->assertOk()
        ->assertViewIs('admin.subscription-plans.index')
        ->assertViewHas('plans');
});

test('admin can view create plan form', function () {
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.subscription-plans.create'))
        ->assertOk()
        ->assertViewIs('admin.subscription-plans.create');
});

test('admin can create a subscription plan', function () {
    $data = [
        'name' => 'Business',
        'slug' => 'business',
        'description' => 'For growing businesses',
        'price' => 1500,
        'billing_cycle' => 'monthly',
        'staff_limit' => 5,
        'customer_limit' => 200,
        'order_limit' => 500,
        'features' => ['reports', 'customer_portal'],
        'is_active' => 1,
        'is_default' => 0,
        'sort_order' => 2,
    ];

    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.subscription-plans.store'), $data)
        ->assertRedirect(route('admin.subscription-plans.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('subscription_plans', [
        'name' => 'Business',
        'slug' => 'business',
        'price' => 1500,
        'staff_limit' => 5,
    ]);
});

test('admin can edit a subscription plan', function () {
    $plan = SubscriptionPlan::factory()->create();

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.subscription-plans.edit', $plan))
        ->assertOk()
        ->assertViewIs('admin.subscription-plans.edit')
        ->assertViewHas('plan');
});

test('admin can update a subscription plan', function () {
    $plan = SubscriptionPlan::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

    $this->actingAs($this->admin, 'admin')
        ->put(route('admin.subscription-plans.update', $plan), [
            'name' => 'Updated Name',
            'slug' => 'updated-name',
            'price' => 999,
            'billing_cycle' => 'monthly',
            'staff_limit' => 3,
            'is_active' => 1,
            'is_default' => 0,
        ])
        ->assertRedirect(route('admin.subscription-plans.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('subscription_plans', [
        'id' => $plan->id,
        'name' => 'Updated Name',
        'slug' => 'updated-name',
    ]);
});

test('admin can delete a plan with no tenants', function () {
    $plan = SubscriptionPlan::factory()->create();

    $this->actingAs($this->admin, 'admin')
        ->delete(route('admin.subscription-plans.destroy', $plan))
        ->assertRedirect(route('admin.subscription-plans.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('subscription_plans', ['id' => $plan->id]);
});

test('admin cannot delete a plan that has tenants', function () {
    $plan = SubscriptionPlan::factory()->create();

    // Manually insert a tenant with this plan
    \Illuminate\Support\Facades\DB::table('tenants')->insert([
        'id' => 'test-tenant',
        'subscription_plan_id' => $plan->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($this->admin, 'admin')
        ->delete(route('admin.subscription-plans.destroy', $plan))
        ->assertRedirect(route('admin.subscription-plans.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id]);
});

test('setting a new default plan clears previous default', function () {
    $oldDefault = SubscriptionPlan::factory()->create([
        'is_default' => true,
        'slug' => 'old-default',
    ]);

    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.subscription-plans.store'), [
            'name' => 'New Default',
            'slug' => 'new-default',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'staff_limit' => 1,
            'is_active' => 1,
            'is_default' => 1,
        ]);

    expect($oldDefault->fresh()->is_default)->toBeFalse();
    expect(SubscriptionPlan::where('slug', 'new-default')->first()->is_default)->toBeTrue();
});

test('validation rejects invalid plan data', function () {
    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.subscription-plans.store'), [
            'name' => '',
            'slug' => '',
            'price' => -1,
            'billing_cycle' => 'weekly',
            'staff_limit' => -5,
        ])
        ->assertSessionHasErrors(['name', 'slug', 'price', 'billing_cycle', 'staff_limit']);
});

test('slug must be unique', function () {
    SubscriptionPlan::factory()->create(['slug' => 'starter']);

    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.subscription-plans.store'), [
            'name' => 'Another Starter',
            'slug' => 'starter',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'staff_limit' => 1,
            'is_active' => 1,
            'is_default' => 0,
        ])
        ->assertSessionHasErrors('slug');
});

test('subscription plan model accessors work correctly', function () {
    $freePlan = SubscriptionPlan::factory()->create([
        'price' => 0,
        'staff_limit' => 1,
        'customer_limit' => 50,
        'order_limit' => 100,
        'features' => ['reports'],
    ]);

    expect($freePlan->isFree())->toBeTrue();
    expect($freePlan->formatted_price)->toBe('Free');
    expect($freePlan->staff_limit_display)->toBe('1');
    expect($freePlan->customer_limit_display)->toBe('50');
    expect($freePlan->order_limit_display)->toBe('100');
    expect($freePlan->hasFeature('reports'))->toBeTrue();
    expect($freePlan->hasFeature('nonexistent'))->toBeFalse();

    $premiumPlan = SubscriptionPlan::factory()->create([
        'price' => 2500,
        'billing_cycle' => 'monthly',
        'staff_limit' => 0,
        'customer_limit' => null,
        'order_limit' => null,
    ]);

    expect($premiumPlan->isFree())->toBeFalse();
    expect($premiumPlan->formatted_price)->toBe('₱2,500.00/monthly');
    expect($premiumPlan->staff_limit_display)->toBe('Unlimited');
    expect($premiumPlan->customer_limit_display)->toBe('Unlimited');
    expect($premiumPlan->order_limit_display)->toBe('Unlimited');
});
