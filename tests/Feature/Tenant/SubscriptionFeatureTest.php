<?php

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-03-29 12:00:00'));

    $tenantKey = 'subscription'.Str::lower(Str::random(8));

    $this->plan = SubscriptionPlan::factory()->premium()->create([
        'billing_cycle' => 'monthly',
    ]);

    $this->tenantDomain = $tenantKey.'.localhost';
    $this->tenant = Tenant::create([
        'id' => $tenantKey,
        'subscription_plan_id' => $this->plan->id,
        'features' => $this->plan->features,
        'is_enabled' => true,
        'is_paid' => true,
        'data' => ['shop_name' => 'Subscription Shop'],
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
    $this->travelBack();

    tenancy()->end();

    if (isset($this->tenant) && $this->tenant->exists) {
        $this->tenant->delete();
    }
});

test('tenant derives the next renewal date from the latest paid subscription payment', function () {
    Payment::create([
        'tenant_id' => $this->tenant->id,
        'subscription_plan_id' => $this->plan->id,
        'payment_type' => 'subscription',
        'amount' => $this->plan->price,
        'currency' => 'PHP',
        'status' => 'paid',
        'description' => 'Older renewal',
        'paid_at' => now()->subMonths(2),
    ]);

    $latestPaidAt = now()->subDays(5);

    Payment::create([
        'tenant_id' => $this->tenant->id,
        'subscription_plan_id' => $this->plan->id,
        'payment_type' => 'subscription',
        'amount' => $this->plan->price,
        'currency' => 'PHP',
        'status' => 'paid',
        'description' => 'Latest renewal',
        'paid_at' => $latestPaidAt,
    ]);

    $this->tenant->load('subscriptionPlan');

    $subscriptionRenewsAt = $this->tenant->subscriptionRenewsAt();

    expect($this->tenant->latestPaidSubscriptionPayment()?->paid_at?->equalTo($latestPaidAt))->toBeTrue();
    expect($subscriptionRenewsAt?->equalTo($latestPaidAt->copy()->addMonth()))->toBeTrue();
    expect($this->tenant->paidDaysRemaining())->toBe(26);
});

test('subscription page shows the next renewal date for paid subscriptions', function () {
    $paidAt = now()->subDays(5);

    Payment::create([
        'tenant_id' => $this->tenant->id,
        'subscription_plan_id' => $this->plan->id,
        'payment_type' => 'subscription',
        'amount' => $this->plan->price,
        'currency' => 'PHP',
        'status' => 'paid',
        'description' => 'Premium renewal',
        'paid_at' => $paidAt,
    ]);

    ($this->loginOwner)();

    $this->get(($this->tenantUrl)('/subscription'))
        ->assertOk()
        ->assertSee('Active Subscription')
        ->assertSee('Next Renewal')
        ->assertSee('Apr 24, 2026')
        ->assertSee('26 days left');
});

test('payment success resets usage counters after a successful subscription checkout', function () {
    $this->tenant->update([
        'is_paid' => false,
        'current_bandwidth_mb' => 321.75,
        'current_api_requests' => 654,
        'usage_reset_at' => now()->subMonth(),
    ]);

    $payment = Payment::create([
        'tenant_id' => $this->tenant->id,
        'subscription_plan_id' => $this->plan->id,
        'payment_type' => 'subscription',
        'paymongo_checkout_id' => 'cs_reset_usage_test',
        'amount' => $this->plan->price,
        'currency' => 'PHP',
        'status' => 'pending',
        'description' => 'Reset usage subscription payment',
    ]);

    Http::fake([
        '*/checkout_sessions/cs_reset_usage_test' => Http::response([
            'data' => [
                'id' => 'cs_reset_usage_test',
                'attributes' => [
                    'status' => 'paid',
                    'checkout_url' => 'https://checkout.paymongo.com/cs_reset_usage_test',
                    'payment_method_used' => 'gcash',
                    'payments' => [['id' => 'pay_reset_usage_test']],
                    'payment_intent' => [
                        'attributes' => ['status' => 'succeeded'],
                    ],
                ],
            ],
        ], 200),
    ]);

    ($this->loginOwner)();

    $this->get(($this->tenantUrl)("/payment/success?payment_id={$payment->id}"))
        ->assertOk();

    $payment->refresh();
    $this->tenant->refresh();

    expect($payment->isPaid())->toBeTrue();
    expect($this->tenant->is_paid)->toBeTrue();
    expect((float) $this->tenant->current_bandwidth_mb)->toBe(0.0);
    expect($this->tenant->current_api_requests)->toBe(0);
    expect($this->tenant->usage_reset_at)->not->toBeNull();
});
