<?php

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\PayMongoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

// ──────────────────────────────────────────────────
// PayMongoService
// ──────────────────────────────────────────────────

test('createCheckoutSession returns id and checkout_url', function () {
    Http::fake([
        '*/checkout_sessions' => Http::response([
            'data' => [
                'id' => 'cs_test_123',
                'attributes' => [
                    'checkout_url' => 'https://checkout.paymongo.com/cs_test_123',
                ],
            ],
        ], 200),
    ]);

    $service = new PayMongoService;

    $result = $service->createCheckoutSession([
        'amount' => 50000,
        'currency' => 'PHP',
        'description' => 'Test Payment',
        'success_url' => 'http://localhost/success',
        'cancel_url' => 'http://localhost/cancel',
    ]);

    expect($result)->toHaveKeys(['id', 'checkout_url']);
    expect($result['id'])->toBe('cs_test_123');
    expect($result['checkout_url'])->toBe('https://checkout.paymongo.com/cs_test_123');
});

test('createCheckoutSession throws on failure', function () {
    Http::fake([
        '*/checkout_sessions' => Http::response(['errors' => []], 400),
    ]);

    $service = new PayMongoService;

    $service->createCheckoutSession([
        'amount' => 50000,
        'description' => 'Fail Test',
        'success_url' => 'http://localhost/success',
        'cancel_url' => 'http://localhost/cancel',
    ]);
})->throws(RuntimeException::class);

test('getCheckoutSession returns data on success', function () {
    Http::fake([
        '*/checkout_sessions/cs_test_456' => Http::response([
            'data' => [
                'id' => 'cs_test_456',
                'attributes' => [
                    'status' => 'active',
                    'checkout_url' => 'https://checkout.paymongo.com/cs_test_456',
                ],
            ],
        ], 200),
    ]);

    $service = new PayMongoService;
    $data = $service->getCheckoutSession('cs_test_456');

    expect($data['id'])->toBe('cs_test_456');
    expect($data['attributes']['status'])->toBe('active');
});

test('getCheckoutSession throws on failure', function () {
    Http::fake([
        '*/checkout_sessions/cs_bad' => Http::response([], 404),
    ]);

    $service = new PayMongoService;
    $service->getCheckoutSession('cs_bad');
})->throws(RuntimeException::class);

test('getCheckoutSessionStatus extracts payment status fields', function () {
    Http::fake([
        '*/checkout_sessions/cs_test_status' => Http::response([
            'data' => [
                'id' => 'cs_test_status',
                'attributes' => [
                    'status' => 'paid',
                    'checkout_url' => 'https://checkout.paymongo.com/cs_test_status',
                    'payment_method_used' => 'gcash',
                    'payments' => [['id' => 'pay_abc123']],
                    'payment_intent' => [
                        'attributes' => ['status' => 'succeeded'],
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new PayMongoService;
    $status = $service->getCheckoutSessionStatus('cs_test_status');

    expect($status['status'])->toBe('succeeded');
    expect($status['link_status'])->toBe('paid');
    expect($status['checkout_url'])->toBe('https://checkout.paymongo.com/cs_test_status');
    expect($status['payment_method'])->toBe('gcash');
    expect($status['payment_id'])->toBe('pay_abc123');
});

test('isCheckoutPaid returns true when status is succeeded', function () {
    Http::fake([
        '*/checkout_sessions/cs_paid' => Http::response([
            'data' => [
                'id' => 'cs_paid',
                'attributes' => [
                    'status' => 'paid',
                    'payment_intent' => [
                        'attributes' => ['status' => 'succeeded'],
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new PayMongoService;

    expect($service->isCheckoutPaid('cs_paid'))->toBeTrue();
});

test('isCheckoutPaid returns false when not paid', function () {
    Http::fake([
        '*/checkout_sessions/cs_pending' => Http::response([
            'data' => [
                'id' => 'cs_pending',
                'attributes' => [
                    'status' => 'active',
                    'payment_intent' => [
                        'attributes' => ['status' => 'awaiting_next_action'],
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new PayMongoService;

    expect($service->isCheckoutPaid('cs_pending'))->toBeFalse();
});

test('isCheckoutPaid returns false on API failure', function () {
    Http::fake([
        '*/checkout_sessions/cs_error' => Http::response([], 500),
    ]);

    $service = new PayMongoService;

    expect($service->isCheckoutPaid('cs_error'))->toBeFalse();
});

test('verifyWebhookSignature validates correct signature', function () {
    config(['services.paymongo.webhook_secret' => 'whsk_test_secret']);
    config(['services.paymongo.secret_key' => 'sk_test_key123']);

    $service = new PayMongoService;
    $timestamp = time();
    $payload = '{"data":"test"}';

    $expectedSig = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsk_test_secret');
    $signatureHeader = "t={$timestamp},te={$expectedSig},li=livesig";

    expect($service->verifyWebhookSignature($payload, $signatureHeader))->toBeTrue();
});

test('verifyWebhookSignature rejects invalid signature', function () {
    config(['services.paymongo.webhook_secret' => 'whsk_test_secret']);
    config(['services.paymongo.secret_key' => 'sk_test_key123']);

    $service = new PayMongoService;

    expect($service->verifyWebhookSignature('payload', 't=123,te=badsig,li=badsig'))->toBeFalse();
});

// ──────────────────────────────────────────────────
// PayMongo Webhook Controller
// ──────────────────────────────────────────────────

test('webhook ignores unknown events', function () {
    $this->postJson(route('webhooks.paymongo'), [
        'data' => [
            'attributes' => [
                'type' => 'some.unknown.event',
                'data' => [],
            ],
        ],
    ])->assertOk()
        ->assertJson(['message' => 'Event ignored']);
});

test('webhook handles checkout paid and activates tenant', function () {
    $plan = SubscriptionPlan::factory()->create(['price' => 500]);

    DB::table('tenants')->insert([
        'id' => 'webhook-test-shop',
        'subscription_plan_id' => $plan->id,
        'is_paid' => false,
        'is_enabled' => true,
        'data' => json_encode(['shop_name' => 'Webhook Test']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payment = Payment::create([
        'tenant_id' => 'webhook-test-shop',
        'subscription_plan_id' => $plan->id,
        'paymongo_checkout_id' => 'cs_webhook_test',
        'amount' => 500,
        'currency' => 'PHP',
        'status' => 'pending',
        'description' => 'Test Payment',
    ]);

    $this->postJson(route('webhooks.paymongo'), [
        'data' => [
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_webhook_test',
                    'attributes' => [
                        'payment_method_used' => 'gcash',
                        'payments' => [['id' => 'pay_webhook_123']],
                    ],
                ],
            ],
        ],
    ])->assertOk()
        ->assertJson(['message' => 'Payment processed']);

    $payment->refresh();
    expect($payment->status)->toBe('paid');
    expect($payment->payment_method)->toBe('gcash');
    expect($payment->paymongo_payment_id)->toBe('pay_webhook_123');
    expect($payment->paid_at)->not->toBeNull();

    $tenant = Tenant::find('webhook-test-shop');
    expect($tenant->is_paid)->toBeTrue();

    DB::table('payments')->where('tenant_id', 'webhook-test-shop')->delete();
    DB::table('tenants')->where('id', 'webhook-test-shop')->delete();
});

test('webhook returns 404 for unknown checkout id', function () {
    $this->postJson(route('webhooks.paymongo'), [
        'data' => [
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_nonexistent',
                    'attributes' => [],
                ],
            ],
        ],
    ])->assertNotFound()
        ->assertJson(['error' => 'Payment not found']);
});

test('webhook skips already paid payments', function () {
    $plan = SubscriptionPlan::factory()->create(['price' => 500]);

    DB::table('tenants')->insert([
        'id' => 'webhook-paid-shop',
        'subscription_plan_id' => $plan->id,
        'is_paid' => true,
        'is_enabled' => true,
        'data' => json_encode(['shop_name' => 'Already Paid']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payment = Payment::create([
        'tenant_id' => 'webhook-paid-shop',
        'subscription_plan_id' => $plan->id,
        'paymongo_checkout_id' => 'cs_already_paid',
        'amount' => 500,
        'currency' => 'PHP',
        'status' => 'paid',
        'paid_at' => now(),
        'description' => 'Already Paid',
    ]);

    $this->postJson(route('webhooks.paymongo'), [
        'data' => [
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_already_paid',
                    'attributes' => [],
                ],
            ],
        ],
    ])->assertOk()
        ->assertJson(['message' => 'Already processed']);

    DB::table('payments')->where('tenant_id', 'webhook-paid-shop')->delete();
    DB::table('tenants')->where('id', 'webhook-paid-shop')->delete();
});
