<?php

use App\Mail\OrderReadyForPickupNotification;
use App\Mail\OrderStatusChangedNotification;
use App\Models\Customer;
use App\Models\CustomerLoyalty;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['queue.default' => 'sync']);

    $tenantKey = 'notify'.Str::lower(Str::random(8));

    $this->tenantDomain = $tenantKey.'.localhost';
    $this->tenant = Tenant::create([
        'id' => $tenantKey,
        'is_enabled' => true,
        'is_paid' => true,
        'features' => [],
        'data' => ['shop_name' => 'Notify Shop'],
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
            'phone' => '09975880520',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $this->customerId = $customer->id;
    });

    $this->tenantUrl = fn (string $path): string => "http://{$this->tenantDomain}{$path}";
    $this->loginOwner = function (): void {
        $this->post(($this->tenantUrl)('/login'), [
            'email' => 'owner@example.com',
            'password' => 'password',
        ])->assertRedirect(route('tenant.dashboard', absolute: false));
    };
    $this->loginCustomer = function (): void {
        $this->post(($this->tenantUrl)('/login'), [
            'email' => 'customer@example.com',
            'password' => 'password',
        ])->assertRedirect(route('tenant.dashboard', absolute: false));
    };
    $this->logoutUser = function (): void {
        $this->post(($this->tenantUrl)('/logout'))->assertRedirect(route('tenant.login', absolute: false));
    };
});

afterEach(function () {
    tenancy()->end();

    if (isset($this->tenant) && $this->tenant->exists) {
        $this->tenant->delete();
    }
});

test('updating an order to ready sends pickup email sms and stores an in app notification', function () {
    $this->tenant->features = ['notifications', 'sms_notifications', 'customer_portal'];
    $this->tenant->save();
    config(['services.sms.token' => 'test-sms-key']);

    $order = $this->tenant->run(function (): Order {
        return Order::create([
            'customer_id' => $this->customerId,
            'order_number' => 'ORD-NOTIFY-0001',
            'status' => 'in_progress',
            'total_amount' => 240,
            'payment_status' => 'unpaid',
        ]);
    });

    Mail::fake();
    Http::fake([
        'https://smsapiph.onrender.com/api/v1/send/sms' => Http::response([
            'success' => true,
        ]),
    ]);

    ($this->loginOwner)();

    $this->from(($this->tenantUrl)("/orders/{$order->id}"))
        ->patch(($this->tenantUrl)("/orders/{$order->id}/status"), [
            'status' => 'ready',
        ])
        ->assertRedirect();

    Mail::assertSent(OrderReadyForPickupNotification::class, function (OrderReadyForPickupNotification $mail): bool {
        return $mail->hasTo('customer@example.com');
    });
    Mail::assertNotSent(OrderStatusChangedNotification::class);
    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://smsapiph.onrender.com/api/v1/send/sms'
            && $request->hasHeader('x-api-key', 'test-sms-key')
            && $request['recipient'] === '+639975880520'
            && $request['message'] === 'LaundryTrack: Order ORD-NOTIFY-0001 is now Ready for Pickup.';
    });

    $notification = $this->tenant->run(function () {
        $customer = Customer::query()->where('email', 'customer@example.com')->firstOrFail();

        return $customer->notifications()->latest()->first();
    });

    expect($notification)->not->toBeNull();
    expect($notification->data['category'])->toBe('order_update');
    expect($notification->data['order_number'])->toBe('ORD-NOTIFY-0001');

    ($this->logoutUser)();
    ($this->loginCustomer)();

    $this->getJson(($this->tenantUrl)('/notifications/feed'))
        ->assertOk()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonPath('notifications.0.category', 'order_update');
});

test('editing an order to ready dispatches notifications too', function () {
    $this->tenant->features = ['notifications', 'sms_notifications', 'customer_portal'];
    $this->tenant->save();
    config(['services.sms.token' => 'test-sms-key']);

    $order = $this->tenant->run(function (): Order {
        return Order::create([
            'customer_id' => $this->customerId,
            'order_number' => 'ORD-NOTIFY-0004',
            'status' => 'in_progress',
            'total_amount' => 180,
            'payment_status' => 'unpaid',
        ]);
    });

    Mail::fake();
    Http::fake([
        'https://smsapiph.onrender.com/api/v1/send/sms' => Http::response([
            'success' => true,
        ]),
    ]);

    ($this->loginOwner)();

    $this->put(($this->tenantUrl)("/orders/{$order->id}"), [
        'customer_id' => $this->customerId,
        'status' => 'ready',
        'items' => [],
    ])->assertRedirect(route('tenant.orders.index', absolute: false));

    Mail::assertSent(OrderReadyForPickupNotification::class, function (OrderReadyForPickupNotification $mail): bool {
        return $mail->hasTo('customer@example.com');
    });
    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://smsapiph.onrender.com/api/v1/send/sms'
            && $request['recipient'] === '+639975880520'
            && $request['message'] === 'LaundryTrack: Order ORD-NOTIFY-0004 is now Ready for Pickup.';
    });

    $notification = $this->tenant->run(function () {
        $customer = Customer::query()->where('email', 'customer@example.com')->firstOrFail();

        return $customer->notifications()->latest()->first();
    });

    expect($notification)->not->toBeNull();
    expect($notification->data['order_number'])->toBe('ORD-NOTIFY-0004');
});

test('updating an order to ready still sends pickup email when sms delivery fails', function () {
    $this->tenant->features = ['notifications', 'sms_notifications', 'customer_portal'];
    $this->tenant->save();
    config(['services.sms.token' => 'test-sms-key']);

    $order = $this->tenant->run(function (): Order {
        return Order::create([
            'customer_id' => $this->customerId,
            'order_number' => 'ORD-NOTIFY-0003',
            'status' => 'in_progress',
            'total_amount' => 260,
            'payment_status' => 'unpaid',
        ]);
    });

    Mail::fake();
    Http::fake([
        'https://smsapiph.onrender.com/api/v1/send/sms' => Http::response([
            'success' => false,
            'error' => 'Carrier unreachable',
        ], 503),
    ]);

    ($this->loginOwner)();

    $this->from(($this->tenantUrl)("/orders/{$order->id}"))
        ->patch(($this->tenantUrl)("/orders/{$order->id}/status"), [
            'status' => 'ready',
        ])
        ->assertRedirect();

    Mail::assertSent(OrderReadyForPickupNotification::class, function (OrderReadyForPickupNotification $mail): bool {
        return $mail->hasTo('customer@example.com');
    });
    Mail::assertNotSent(OrderStatusChangedNotification::class);
    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://smsapiph.onrender.com/api/v1/send/sms'
            && $request->hasHeader('x-api-key', 'test-sms-key')
            && $request['recipient'] === '+639975880520'
            && $request['message'] === 'LaundryTrack: Order ORD-NOTIFY-0003 is now Ready for Pickup.';
    });

    $notification = $this->tenant->run(function () {
        $customer = Customer::query()->where('email', 'customer@example.com')->firstOrFail();

        return $customer->notifications()->latest()->first();
    });

    expect($notification)->not->toBeNull();
    expect($notification->data['category'])->toBe('order_update');
    expect($notification->data['order_number'])->toBe('ORD-NOTIFY-0003');
});

test('mark all read clears unread notification counts without sending sms for non ready updates', function () {
    $this->tenant->features = ['notifications', 'sms_notifications', 'customer_portal'];
    $this->tenant->save();
    config(['services.sms.token' => 'test-sms-key']);

    $order = $this->tenant->run(function (): Order {
        return Order::create([
            'customer_id' => $this->customerId,
            'order_number' => 'ORD-NOTIFY-0002',
            'status' => 'received',
            'total_amount' => 150,
            'payment_status' => 'unpaid',
        ]);
    });

    Mail::fake();
    Http::fake();

    ($this->loginOwner)();

    $this->from(($this->tenantUrl)("/orders/{$order->id}"))
        ->patch(($this->tenantUrl)("/orders/{$order->id}/status"), [
            'status' => 'in_progress',
        ])
        ->assertRedirect();

    ($this->logoutUser)();
    ($this->loginCustomer)();

    $this->from(($this->tenantUrl)('/notifications'))
        ->patch(($this->tenantUrl)('/notifications/read-all'))
        ->assertRedirect();

    $this->getJson(($this->tenantUrl)('/notifications/feed'))
        ->assertOk()
        ->assertJsonPath('unread_count', 0);

    $readAt = $this->tenant->run(function () {
        $customer = Customer::query()->where('email', 'customer@example.com')->firstOrFail();

        return $customer->notifications()->latest()->first()?->read_at;
    });

    Http::assertNothingSent();
    expect($readAt)->not->toBeNull();
});

test('claiming an order awards loyalty even without the notifications feature and only once', function () {
    $this->tenant->features = ['customer_loyalty'];
    $this->tenant->save();

    $order = $this->tenant->run(function (): Order {
        return Order::create([
            'customer_id' => $this->customerId,
            'order_number' => 'ORD-LOYAL-0001',
            'status' => 'ready',
            'total_amount' => 350,
            'payment_status' => 'paid',
        ]);
    });

    ($this->loginOwner)();

    $this->from(($this->tenantUrl)("/orders/{$order->id}"))
        ->patch(($this->tenantUrl)("/orders/{$order->id}/status"), [
            'status' => 'claimed',
        ])
        ->assertRedirect();

    $this->from(($this->tenantUrl)("/orders/{$order->id}"))
        ->patch(($this->tenantUrl)("/orders/{$order->id}/status"), [
            'status' => 'claimed',
        ])
        ->assertRedirect();

    $loyalty = $this->tenant->run(fn (): ?CustomerLoyalty => CustomerLoyalty::query()->first());
    $updatedOrder = $this->tenant->run(fn (): ?Order => Order::query()->where('order_number', 'ORD-LOYAL-0001')->first());

    expect($loyalty)->not->toBeNull();
    expect($loyalty->points)->toBe(3);
    expect($loyalty->stamps)->toBe(1);
    expect((float) $loyalty->lifetime_spent)->toBe(350.0);
    expect($updatedOrder)->not->toBeNull();
    expect($updatedOrder->loyalty_points_awarded)->toBe(3);
    expect($updatedOrder->loyalty_points_awarded_at)->not->toBeNull();
});

test('customer portal shows the loyalty summary when the feature is enabled', function () {
    $this->tenant->features = ['customer_portal', 'customer_loyalty'];
    $this->tenant->save();

    $this->tenant->run(function (): void {
        CustomerLoyalty::create([
            'customer_id' => $this->customerId,
            'points' => 24,
            'stamps' => 6,
            'tier' => 'silver',
            'lifetime_spent' => 12000,
        ]);
    });

    ($this->loginCustomer)();

    $this->get(($this->tenantUrl)('/portal'))
        ->assertOk()
        ->assertSee('Loyalty Rewards')
        ->assertSee('Silver Tier')
        ->assertSee('24')
        ->assertSee('Stamps');
});
