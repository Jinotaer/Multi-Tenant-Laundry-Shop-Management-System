<?php

use App\Models\Admin;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantRegistration;
use App\Services\PlanLimitService;
use Illuminate\Support\Facades\DB;

// ──────────────────────────────────────────────────
// Registration with Plan Selection
// ──────────────────────────────────────────────────

test('registration form displays active plans', function () {
    SubscriptionPlan::factory()->create(['name' => 'Starter', 'is_active' => true]);
    SubscriptionPlan::factory()->create(['name' => 'Premium', 'is_active' => true]);
    SubscriptionPlan::factory()->inactive()->create(['name' => 'Archived']);

    $this->get(route('shop.register'))
        ->assertOk()
        ->assertSee('Starter')
        ->assertSee('Premium')
        ->assertDontSee('Archived');
});

test('shop registration stores selected plan id', function () {
    $plan = SubscriptionPlan::factory()->create();

    $this->post(route('shop.register'), [
        'shop_name' => 'Test Laundry',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'subscription_plan_id' => $plan->id,
    ])->assertRedirect(route('shop.pending'));

    $this->assertDatabaseHas('tenant_registrations', [
        'owner_email' => 'john@example.com',
        'subscription_plan_id' => $plan->id,
    ]);
});

test('shop registration defaults to default plan when none selected', function () {
    $default = SubscriptionPlan::factory()->create(['is_default' => true]);
    SubscriptionPlan::factory()->create(['is_default' => false]);

    $this->post(route('shop.register'), [
        'shop_name' => 'Another Laundry',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('shop.pending'));

    $this->assertDatabaseHas('tenant_registrations', [
        'owner_email' => 'jane@example.com',
        'subscription_plan_id' => $default->id,
    ]);
});

test('shop registration rejects invalid plan id', function () {
    $this->post(route('shop.register'), [
        'shop_name' => 'Bad Plan Laundry',
        'name' => 'Bad Plan',
        'email' => 'bad@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'subscription_plan_id' => 99999,
    ])->assertSessionHasErrors('subscription_plan_id');
});

// ──────────────────────────────────────────────────
// Admin: Change Tenant Plan
// ──────────────────────────────────────────────────

test('admin can update tenant plan', function () {
    $admin = Admin::factory()->create();
    $oldPlan = SubscriptionPlan::factory()->free()->create();
    $newPlan = SubscriptionPlan::factory()->premium()->create();

    // Use raw DB insert to avoid Tenant::create triggering tenant DB creation
    DB::table('tenants')->insert([
        'id' => 'plan-test-shop',
        'subscription_plan_id' => $oldPlan->id,
        'data' => json_encode(['shop_name' => 'Plan Test']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('plan-test-shop');

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.tenants.update-plan', $tenant), [
            'subscription_plan_id' => $newPlan->id,
        ])
        ->assertRedirect();

    $tenant->refresh();
    expect($tenant->subscription_plan_id)->toBe($newPlan->id);
    expect($tenant->features)->toBe($newPlan->features);

    DB::table('tenants')->where('id', 'plan-test-shop')->delete();
});

test('admin cannot assign nonexistent plan to tenant', function () {
    $admin = Admin::factory()->create();
    $plan = SubscriptionPlan::factory()->create();

    DB::table('tenants')->insert([
        'id' => 'plan-test-shop-2',
        'subscription_plan_id' => $plan->id,
        'data' => json_encode(['shop_name' => 'Plan Test 2']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('plan-test-shop-2');

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.tenants.update-plan', $tenant), [
            'subscription_plan_id' => 99999,
        ])
        ->assertSessionHasErrors('subscription_plan_id');

    DB::table('tenants')->where('id', 'plan-test-shop-2')->delete();
});

test('updating tenant plan syncs features from new plan', function () {
    $admin = Admin::factory()->create();
    $freePlan = SubscriptionPlan::factory()->create([
        'features' => ['reports'],
    ]);
    $premiumPlan = SubscriptionPlan::factory()->create([
        'features' => ['reports', 'online_payments', 'sms_notifications', 'customer_portal', 'inventory_management'],
    ]);

    DB::table('tenants')->insert([
        'id' => 'feature-sync-shop',
        'subscription_plan_id' => $freePlan->id,
        'features' => json_encode(['reports']),
        'data' => json_encode(['shop_name' => 'Feature Sync']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('feature-sync-shop');

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.tenants.update-plan', $tenant), [
            'subscription_plan_id' => $premiumPlan->id,
        ]);

    $tenant->refresh();
    expect($tenant->features)->toBe($premiumPlan->features);

    DB::table('tenants')->where('id', 'feature-sync-shop')->delete();
});

// ──────────────────────────────────────────────────
// PlanLimitService
// ──────────────────────────────────────────────────

test('plan limit service allows actions when no plan assigned', function () {
    DB::table('tenants')->insert([
        'id' => 'no-plan-shop',
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('no-plan-shop');
    $service = new PlanLimitService($tenant);

    expect($service->canAddStaff(100))->toBeTrue();
    expect($service->canAddCustomer(100))->toBeTrue();
    expect($service->canAddOrder(100))->toBeTrue();

    DB::table('tenants')->where('id', 'no-plan-shop')->delete();
});

test('plan limit service enforces staff limit', function () {
    $plan = SubscriptionPlan::factory()->create(['staff_limit' => 3]);

    DB::table('tenants')->insert([
        'id' => 'staff-limit-shop',
        'subscription_plan_id' => $plan->id,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('staff-limit-shop');
    $service = new PlanLimitService($tenant);

    expect($service->canAddStaff(2))->toBeTrue();
    expect($service->canAddStaff(3))->toBeFalse();
    expect($service->canAddStaff(5))->toBeFalse();

    DB::table('tenants')->where('id', 'staff-limit-shop')->delete();
});

test('plan limit service allows unlimited staff when limit is zero', function () {
    $plan = SubscriptionPlan::factory()->create(['staff_limit' => 0]);

    DB::table('tenants')->insert([
        'id' => 'unlimited-staff-shop',
        'subscription_plan_id' => $plan->id,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('unlimited-staff-shop');
    $service = new PlanLimitService($tenant);

    expect($service->canAddStaff(999))->toBeTrue();

    DB::table('tenants')->where('id', 'unlimited-staff-shop')->delete();
});

test('plan limit service enforces customer limit', function () {
    $plan = SubscriptionPlan::factory()->create(['customer_limit' => 50]);

    DB::table('tenants')->insert([
        'id' => 'cust-limit-shop',
        'subscription_plan_id' => $plan->id,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('cust-limit-shop');
    $service = new PlanLimitService($tenant);

    expect($service->canAddCustomer(49))->toBeTrue();
    expect($service->canAddCustomer(50))->toBeFalse();

    DB::table('tenants')->where('id', 'cust-limit-shop')->delete();
});

test('plan limit service allows unlimited customers when limit is null', function () {
    $plan = SubscriptionPlan::factory()->create(['customer_limit' => null]);

    DB::table('tenants')->insert([
        'id' => 'unlimited-cust-shop',
        'subscription_plan_id' => $plan->id,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('unlimited-cust-shop');
    $service = new PlanLimitService($tenant);

    expect($service->canAddCustomer(99999))->toBeTrue();

    DB::table('tenants')->where('id', 'unlimited-cust-shop')->delete();
});

test('plan limit service enforces order limit', function () {
    $plan = SubscriptionPlan::factory()->create(['order_limit' => 100]);

    DB::table('tenants')->insert([
        'id' => 'order-limit-shop',
        'subscription_plan_id' => $plan->id,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('order-limit-shop');
    $service = new PlanLimitService($tenant);

    expect($service->canAddOrder(99))->toBeTrue();
    expect($service->canAddOrder(100))->toBeFalse();

    DB::table('tenants')->where('id', 'order-limit-shop')->delete();
});

test('plan limit service returns correct usage summary', function () {
    $plan = SubscriptionPlan::factory()->create([
        'staff_limit' => 5,
        'customer_limit' => 100,
        'order_limit' => 200,
    ]);

    DB::table('tenants')->insert([
        'id' => 'usage-summary-shop',
        'subscription_plan_id' => $plan->id,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('usage-summary-shop');
    $service = new PlanLimitService($tenant);
    $usage = $service->getUsageSummary(3, 75, 150);

    expect($usage['staff']['current'])->toBe(3);
    expect($usage['staff']['limit'])->toBe(5);
    expect($usage['staff']['percentage'])->toEqual(60);

    expect($usage['customers']['current'])->toBe(75);
    expect($usage['customers']['limit'])->toBe(100);
    expect($usage['customers']['percentage'])->toEqual(75);

    expect($usage['orders']['current'])->toBe(150);
    expect($usage['orders']['limit'])->toBe(200);
    expect($usage['orders']['percentage'])->toEqual(75);

    DB::table('tenants')->where('id', 'usage-summary-shop')->delete();
});

test('plan limit service returns remaining counts', function () {
    $plan = SubscriptionPlan::factory()->create([
        'staff_limit' => 5,
        'customer_limit' => 50,
        'order_limit' => null,
    ]);

    DB::table('tenants')->insert([
        'id' => 'remaining-shop',
        'subscription_plan_id' => $plan->id,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('remaining-shop');
    $service = new PlanLimitService($tenant);

    expect($service->remainingStaff(3))->toBe(2);
    expect($service->remainingCustomers(48))->toBe(2);
    expect($service->remainingOrders(999))->toBeNull(); // unlimited

    DB::table('tenants')->where('id', 'remaining-shop')->delete();
});

// ──────────────────────────────────────────────────
// TenantRegistration Model
// ──────────────────────────────────────────────────

test('tenant registration belongs to subscription plan', function () {
    $plan = SubscriptionPlan::factory()->create();

    $registration = TenantRegistration::create([
        'shop_name' => 'Relationship Test Shop',
        'subdomain' => 'rel-test',
        'owner_name' => 'Test Owner',
        'owner_email' => 'reltest@example.com',
        'owner_password' => 'password',
        'subscription_plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    expect($registration->subscriptionPlan)->toBeInstanceOf(SubscriptionPlan::class);
    expect($registration->subscriptionPlan->id)->toBe($plan->id);
});


test('admin can approve a pending registration and create tenant', function () {
    \Illuminate\Support\Facades\Mail::fake();

    $admin = \App\Models\Admin::factory()->create();
    $freePlan = SubscriptionPlan::factory()->free()->create();

    $registration = TenantRegistration::create([
        'shop_name' => 'Approve Shop',
        'subdomain' => 'approve-shop',
        'owner_name' => 'Approve Owner',
        'owner_email' => 'approve@example.com',
        'owner_password' => 'password',
        'subscription_plan_id' => $freePlan->id,
        'status' => 'pending',
    ]);

    // approval should be reachable with POST; PATCH is not defined anymore
    $this->actingAs($admin, 'admin')
        ->post(route('admin.registrations.approve', $registration))
        ->assertRedirect();

    // ensure old PATCH route is no longer available (method not allowed)
    $this->actingAs($admin, 'admin')
        ->patch(route('admin.registrations.approve', $registration))
        ->assertStatus(405);

    $registration->refresh();
    expect($registration->isApproved())->toBeTrue();
    expect($registration->approved_at)->not->toBeNull();

    $tenant = Tenant::find('approve-shop');
    expect($tenant)->not->toBeNull();
    expect($tenant->trial_ends_at)->not->toBeNull();

    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\TenantApproved::class);

    // Cleanup
    $tenant->delete();
});


test('admin can reject a pending registration and notify owner', function () {
    \Illuminate\Support\Facades\Mail::fake();

    $admin = \App\Models\Admin::factory()->create();

    $registration = TenantRegistration::create([
        'shop_name' => 'Reject Shop',
        'subdomain' => 'reject-shop',
        'owner_name' => 'Reject Owner',
        'owner_email' => 'reject@example.com',
        'owner_password' => 'password',
        'status' => 'pending',
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.registrations.reject', $registration), [
            'rejection_reason' => 'Invalid details',
        ])
        ->assertRedirect();

    $registration->refresh();
    expect($registration->isRejected())->toBeTrue();
    expect($registration->rejection_reason)->toBe('Invalid details');

    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\TenantRejected::class);
});
