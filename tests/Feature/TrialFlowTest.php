<?php

use App\Models\Admin;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

// ──────────────────────────────────────────────────
// Pricing Page (Plan Selection)
// ──────────────────────────────────────────────────

test('pricing page displays active plans', function () {
    SubscriptionPlan::factory()->create(['name' => 'Starter', 'is_active' => true]);
    SubscriptionPlan::factory()->create(['name' => 'Premium', 'is_active' => true]);
    SubscriptionPlan::factory()->inactive()->create(['name' => 'Archived']);

    $this->get(route('shop.pricing'))
        ->assertOk()
        ->assertSee('Starter')
        ->assertSee('Premium')
        ->assertDontSee('Archived');
});

test('pricing plan links to registration with plan id', function () {
    $plan = SubscriptionPlan::factory()->create(['name' => 'Premium', 'is_active' => true]);

    $this->get(route('shop.pricing'))
        ->assertOk()
        ->assertSee(route('shop.register', ['plan' => $plan->id]));
});

// ──────────────────────────────────────────────────
// Registration With Plan From Query Param
// ──────────────────────────────────────────────────

test('registration page shows selected plan from query parameter', function () {
    $plan = SubscriptionPlan::factory()->create(['name' => 'Premium']);

    $this->get(route('shop.register', ['plan' => $plan->id]))
        ->assertOk()
        ->assertSee('Premium Plan');
});

test('registration page falls back to default plan when no plan param', function () {
    $default = SubscriptionPlan::factory()->create(['name' => 'Starter', 'is_default' => true]);

    $this->get(route('shop.register'))
        ->assertOk()
        ->assertSee('Starter Plan');
});

test('registration stores plan from hidden field', function () {
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

// ──────────────────────────────────────────────────
// Tenant Model: Trial Helper Methods
// ──────────────────────────────────────────────────

test('tenant isOnTrial returns true when trial is in the future', function () {
    DB::table('tenants')->insert([
        'id' => 'trial-future',
        'trial_ends_at' => now()->addDays(15),
        'is_paid' => false,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('trial-future');

    expect($tenant->isOnTrial())->toBeTrue();
    expect($tenant->isTrialExpired())->toBeFalse();

    DB::table('tenants')->where('id', 'trial-future')->delete();
});

test('tenant isTrialExpired returns true when trial is past', function () {
    DB::table('tenants')->insert([
        'id' => 'trial-past',
        'trial_ends_at' => now()->subDays(1),
        'is_paid' => false,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('trial-past');

    expect($tenant->isOnTrial())->toBeFalse();
    expect($tenant->isTrialExpired())->toBeTrue();

    DB::table('tenants')->where('id', 'trial-past')->delete();
});

test('tenant hasActiveSubscription is true when paid', function () {
    DB::table('tenants')->insert([
        'id' => 'paid-tenant',
        'trial_ends_at' => now()->subDays(10),
        'is_paid' => true,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('paid-tenant');

    expect($tenant->hasActiveSubscription())->toBeTrue();

    DB::table('tenants')->where('id', 'paid-tenant')->delete();
});

test('tenant hasActiveSubscription is true when on trial', function () {
    DB::table('tenants')->insert([
        'id' => 'trial-active',
        'trial_ends_at' => now()->addDays(20),
        'is_paid' => false,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('trial-active');

    expect($tenant->hasActiveSubscription())->toBeTrue();

    DB::table('tenants')->where('id', 'trial-active')->delete();
});

test('tenant hasActiveSubscription is false when trial expired and not paid', function () {
    DB::table('tenants')->insert([
        'id' => 'expired-unpaid',
        'trial_ends_at' => now()->subDays(5),
        'is_paid' => false,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('expired-unpaid');

    expect($tenant->hasActiveSubscription())->toBeFalse();

    DB::table('tenants')->where('id', 'expired-unpaid')->delete();
});

test('tenant trialDaysRemaining returns correct count', function () {
    DB::table('tenants')->insert([
        'id' => 'trial-days',
        'trial_ends_at' => now()->addDays(12),
        'is_paid' => false,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('trial-days');

    expect($tenant->trialDaysRemaining())->toBeGreaterThanOrEqual(11)->toBeLessThanOrEqual(12);

    DB::table('tenants')->where('id', 'trial-days')->delete();
});

test('tenant trialDaysRemaining returns zero when expired', function () {
    DB::table('tenants')->insert([
        'id' => 'trial-zero',
        'trial_ends_at' => now()->subDays(3),
        'is_paid' => false,
        'data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('trial-zero');

    expect($tenant->trialDaysRemaining())->toBe(0);

    DB::table('tenants')->where('id', 'trial-zero')->delete();
});

// ──────────────────────────────────────────────────
// Admin: Mark Paid / Unpaid
// ──────────────────────────────────────────────────

test('admin can mark tenant as paid', function () {
    $admin = Admin::factory()->create();
    $plan = SubscriptionPlan::factory()->create();

    DB::table('tenants')->insert([
        'id' => 'mark-paid-shop',
        'subscription_plan_id' => $plan->id,
        'trial_ends_at' => now()->subDays(5),
        'is_paid' => false,
        'is_enabled' => false,
        'data' => json_encode(['shop_name' => 'Mark Paid Shop']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('mark-paid-shop');

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.tenants.mark-paid', $tenant))
        ->assertRedirect();

    $tenant->refresh();
    expect($tenant->is_paid)->toBeTrue();
    expect($tenant->is_enabled)->toBeTrue();

    DB::table('tenants')->where('id', 'mark-paid-shop')->delete();
});

test('admin can revoke paid status', function () {
    $admin = Admin::factory()->create();
    $plan = SubscriptionPlan::factory()->create();

    DB::table('tenants')->insert([
        'id' => 'mark-unpaid-shop',
        'subscription_plan_id' => $plan->id,
        'trial_ends_at' => now()->subDays(5),
        'is_paid' => true,
        'data' => json_encode(['shop_name' => 'Mark Unpaid Shop']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tenant = Tenant::find('mark-unpaid-shop');

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.tenants.mark-unpaid', $tenant))
        ->assertRedirect();

    $tenant->refresh();
    expect($tenant->is_paid)->toBeFalse();

    DB::table('tenants')->where('id', 'mark-unpaid-shop')->delete();
});

// ──────────────────────────────────────────────────
// Admin Approval Removed — Auto-Creation Sets Trial
// ──────────────────────────────────────────────────

test('registering a shop creates a pending registration (no tenant created)', function () {
    $plan = SubscriptionPlan::factory()->free()->create(['is_default' => true]);

    $this->post(route('shop.register'), [
        'shop_name' => 'Pending Shop',
        'name' => 'Pending Owner',
        'email' => 'pending@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'subscription_plan_id' => $plan->id,
    ])->assertRedirect(route('shop.pending'));

    $this->assertDatabaseHas('tenant_registrations', [
        'owner_email' => 'pending@example.com',
        'status' => 'pending',
    ]);

    // No tenant should be created yet (admin must approve)
    $this->assertNull(Tenant::find('pending-shop'));
});

// ──────────────────────────────────────────────────
// Expire Trials Command
// ──────────────────────────────────────────────────

test('expire trials command disables expired unpaid tenants', function () {
    DB::table('tenants')->insert([
        'id' => 'expired-cmd-shop',
        'trial_ends_at' => now()->subDays(2),
        'is_paid' => false,
        'is_enabled' => true,
        'data' => json_encode(['shop_name' => 'Expired CMD Shop']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('tenants:expire-trials')
        ->assertExitCode(0);

    $tenant = Tenant::find('expired-cmd-shop');
    expect($tenant->is_enabled)->toBeFalse();

    DB::table('tenants')->where('id', 'expired-cmd-shop')->delete();
});

test('expire trials command does not disable paid tenants', function () {
    DB::table('tenants')->insert([
        'id' => 'paid-cmd-shop',
        'trial_ends_at' => now()->subDays(2),
        'is_paid' => true,
        'is_enabled' => true,
        'data' => json_encode(['shop_name' => 'Paid CMD Shop']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('tenants:expire-trials')
        ->assertExitCode(0);

    $tenant = Tenant::find('paid-cmd-shop');
    expect($tenant->is_enabled)->toBeTrue();

    DB::table('tenants')->where('id', 'paid-cmd-shop')->delete();
});

test('expire trials command does not disable active trial tenants', function () {
    DB::table('tenants')->insert([
        'id' => 'active-cmd-shop',
        'trial_ends_at' => now()->addDays(10),
        'is_paid' => false,
        'is_enabled' => true,
        'data' => json_encode(['shop_name' => 'Active CMD Shop']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('tenants:expire-trials')
        ->assertExitCode(0);

    $tenant = Tenant::find('active-cmd-shop');
    expect($tenant->is_enabled)->toBeTrue();

    DB::table('tenants')->where('id', 'active-cmd-shop')->delete();
});

// ──────────────────────────────────────────────────
// Premium Plan Registration (No Trial)
// ──────────────────────────────────────────────────

test('registering with a premium plan creates a pending registration (no tenant yet)', function () {
    $premiumPlan = SubscriptionPlan::factory()->premium()->create();

    $this->post(route('shop.register'), [
        'shop_name' => 'Premium Pending Shop',
        'name' => 'Premium Owner',
        'email' => 'premiumowner@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'subscription_plan_id' => $premiumPlan->id,
    ])->assertRedirect(route('shop.pending'));

    $this->assertDatabaseHas('tenant_registrations', [
        'owner_email' => 'premiumowner@example.com',
        'status' => 'pending',
    ]);

    $this->assertNull(Tenant::find('premium-pending-shop'));
});

test('registering with a free plan creates a pending registration (no tenant yet)', function () {
    $freePlan = SubscriptionPlan::factory()->free()->create();

    $this->post(route('shop.register'), [
        'shop_name' => 'Free Pending Shop',
        'name' => 'Free Owner',
        'email' => 'freeowner@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'subscription_plan_id' => $freePlan->id,
    ])->assertRedirect(route('shop.pending'));

    $this->assertDatabaseHas('tenant_registrations', [
        'owner_email' => 'freeowner@example.com',
        'status' => 'pending',
    ]);

    $this->assertNull(Tenant::find('free-pending-shop'));
});

// ──────────────────────────────────────────────────
// Payment Model
// ──────────────────────────────────────────────────

test('payment isPaid returns true for paid status', function () {
    $plan = SubscriptionPlan::factory()->premium()->create();

    DB::table('tenants')->insert([
        'id' => 'payment-model-shop',
        'subscription_plan_id' => $plan->id,
        'is_paid' => false,
        'data' => json_encode(['shop_name' => 'Payment Model Shop']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payment = \App\Models\Payment::create([
        'tenant_id' => 'payment-model-shop',
        'subscription_plan_id' => $plan->id,
        'amount' => 1500.00,
        'currency' => 'PHP',
        'status' => 'paid',
        'description' => 'Test payment',
        'paid_at' => now(),
    ]);

    expect($payment->isPaid())->toBeTrue();
    expect($payment->isPending())->toBeFalse();

    DB::table('payments')->where('id', $payment->id)->delete();
    DB::table('tenants')->where('id', 'payment-model-shop')->delete();
});

test('payment isPending returns true for pending status', function () {
    $plan = SubscriptionPlan::factory()->premium()->create();

    DB::table('tenants')->insert([
        'id' => 'payment-pending-shop',
        'subscription_plan_id' => $plan->id,
        'is_paid' => false,
        'data' => json_encode(['shop_name' => 'Payment Pending Shop']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payment = \App\Models\Payment::create([
        'tenant_id' => 'payment-pending-shop',
        'subscription_plan_id' => $plan->id,
        'amount' => 1500.00,
        'currency' => 'PHP',
        'status' => 'pending',
        'description' => 'Pending payment',
    ]);

    expect($payment->isPending())->toBeTrue();
    expect($payment->isPaid())->toBeFalse();

    DB::table('payments')->where('id', $payment->id)->delete();
    DB::table('tenants')->where('id', 'payment-pending-shop')->delete();
});

// ──────────────────────────────────────────────────
// PayMongo Webhook
// ──────────────────────────────────────────────────

test('paymongo webhook marks payment as paid and activates tenant', function () {
    // Disable webhook signature verification for testing
    config(['services.paymongo.webhook_secret' => '']);

    $plan = SubscriptionPlan::factory()->premium()->create();

    DB::table('tenants')->insert([
        'id' => 'webhook-shop',
        'subscription_plan_id' => $plan->id,
        'is_paid' => false,
        'is_enabled' => true,
        'data' => json_encode(['shop_name' => 'Webhook Shop']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payment = \App\Models\Payment::create([
        'tenant_id' => 'webhook-shop',
        'subscription_plan_id' => $plan->id,
        'paymongo_checkout_id' => 'cs_test_abc123',
        'amount' => 1500.00,
        'currency' => 'PHP',
        'status' => 'pending',
        'description' => 'Premium Plan — Webhook Shop',
    ]);

    // Simulate webhook payload (no signature verification since secret is empty in tests)
    $payload = [
        'data' => [
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_test_abc123',
                    'attributes' => [
                        'payment_method_used' => 'gcash',
                        'payments' => [
                            ['id' => 'pay_test_123'],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $this->postJson(route('webhooks.paymongo'), $payload)
        ->assertOk()
        ->assertJson(['message' => 'Payment processed']);

    $payment->refresh();
    expect($payment->status)->toBe('paid');
    expect($payment->payment_method)->toBe('gcash');
    expect($payment->paid_at)->not->toBeNull();

    $tenant = Tenant::find('webhook-shop');
    expect($tenant->is_paid)->toBeTrue();

    DB::table('payments')->where('id', $payment->id)->delete();
    DB::table('tenants')->where('id', 'webhook-shop')->delete();
});

test('paymongo webhook ignores already paid payments', function () {
    // Disable webhook signature verification for testing
    config(['services.paymongo.webhook_secret' => '']);

    $plan = SubscriptionPlan::factory()->premium()->create();

    DB::table('tenants')->insert([
        'id' => 'webhook-dup-shop',
        'subscription_plan_id' => $plan->id,
        'is_paid' => true,
        'data' => json_encode(['shop_name' => 'Webhook Dup Shop']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payment = \App\Models\Payment::create([
        'tenant_id' => 'webhook-dup-shop',
        'subscription_plan_id' => $plan->id,
        'paymongo_checkout_id' => 'cs_test_dup456',
        'amount' => 1500.00,
        'currency' => 'PHP',
        'status' => 'paid',
        'description' => 'Already paid',
        'paid_at' => now(),
    ]);

    $payload = [
        'data' => [
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_test_dup456',
                    'attributes' => [],
                ],
            ],
        ],
    ];

    $this->postJson(route('webhooks.paymongo'), $payload)
        ->assertOk()
        ->assertJson(['message' => 'Already processed']);

    DB::table('payments')->where('id', $payment->id)->delete();
    DB::table('tenants')->where('id', 'webhook-dup-shop')->delete();
});
