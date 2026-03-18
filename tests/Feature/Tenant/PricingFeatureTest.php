<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    $tenantKey = 'pricing'.Str::lower(Str::random(8));

    $this->tenantDomain = $tenantKey.'.localhost';
    $this->tenant = Tenant::create([
        'id' => $tenantKey,
        'is_enabled' => true,
        'is_paid' => true,
        'features' => ['simple_pricing'],
        'data' => ['shop_name' => 'Pricing Shop'],
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
            'name' => 'Pricing Customer',
            'email' => 'customer@example.com',
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
});

afterEach(function () {
    tenancy()->end();

    if (isset($this->tenant) && $this->tenant->exists) {
        $this->tenant->delete();
    }
});

test('simple pricing service form only exposes per kilo pricing and rejects advanced types', function () {
    ($this->loginOwner)();

    $this->get(($this->tenantUrl)('/services/create'))
        ->assertOk()
        ->assertSee('Simple pricing is enabled for this shop.')
        ->assertSee('Per Kilo')
        ->assertDontSee('Per Load')
        ->assertDontSee('Per Piece')
        ->assertDontSee('Flat Rate');

    $this->post(($this->tenantUrl)('/services'), [
        'name' => 'Load Wash',
        'price_type' => 'per_load',
        'price' => 120,
        'is_active' => 1,
    ])->assertSessionHasErrors(['price_type']);

    $serviceCount = $this->tenant->run(fn (): int => Service::count());

    expect($serviceCount)->toBe(0);
});

test('advanced pricing service form exposes extended pricing options', function () {
    $this->tenant->features = ['advanced_pricing'];
    $this->tenant->save();

    ($this->loginOwner)();

    $this->get(($this->tenantUrl)('/services/create'))
        ->assertOk()
        ->assertSee('Advanced pricing is enabled for this shop.')
        ->assertSee('Per Kilo')
        ->assertSee('Per Load')
        ->assertSee('Per Piece')
        ->assertSee('Flat Rate');
});

test('per kilo orders require weight and recalculate totals on the server', function () {
    $service = $this->tenant->run(function (): Service {
        return Service::create([
            'name' => 'Wash and Fold',
            'price_type' => 'per_kilo',
            'price' => 60,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    });

    ($this->loginOwner)();

    $basePayload = [
        'customer_id' => $this->customerId,
        'service_id' => $service->id,
        'status' => 'received',
        'items' => [
            ['name' => 'Detergent', 'qty' => 2, 'price' => 30],
        ],
        'total_amount' => 1,
    ];

    $this->post(($this->tenantUrl)('/orders'), $basePayload)
        ->assertSessionHasErrors(['weight']);

    $this->post(($this->tenantUrl)('/orders'), $basePayload + ['weight' => 2.5])
        ->assertRedirect(route('tenant.orders.index', absolute: false));

    $order = $this->tenant->run(fn (): ?Order => Order::query()->latest('id')->first());

    expect($order)->not->toBeNull();
    expect((float) $order->weight)->toBe(2.5);
    expect((float) $order->total_amount)->toBe(210.0);
    expect($order->items)->toHaveCount(1);
    expect($order->items[0]['name'])->toBe('Detergent');
    expect((int) $order->items[0]['qty'])->toBe(2);
    expect((float) $order->items[0]['price'])->toBe(30.0);
});

test('advanced per piece orders use the service price as the default line price', function () {
    $this->tenant->features = ['advanced_pricing'];
    $this->tenant->save();

    $service = $this->tenant->run(function (): Service {
        return Service::create([
            'name' => 'Pressing',
            'price_type' => 'per_piece',
            'price' => 15,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    });

    ($this->loginOwner)();

    $this->post(($this->tenantUrl)('/orders'), [
        'customer_id' => $this->customerId,
        'service_id' => $service->id,
        'status' => 'received',
        'items' => [
            ['name' => 'Shirt', 'qty' => 3, 'price' => ''],
            ['name' => 'Dress', 'qty' => 1, 'price' => 25],
        ],
        'total_amount' => 1,
    ])->assertRedirect(route('tenant.orders.index', absolute: false));

    $order = $this->tenant->run(fn (): ?Order => Order::query()->latest('id')->first());

    expect($order)->not->toBeNull();
    expect($order->weight)->toBeNull();
    expect((float) $order->total_amount)->toBe(70.0);
    expect($order->items)->toHaveCount(2);
    expect($order->items[0]['name'])->toBe('Shirt');
    expect((int) $order->items[0]['qty'])->toBe(3);
    expect((float) $order->items[0]['price'])->toBe(15.0);
    expect($order->items[1]['name'])->toBe('Dress');
    expect((int) $order->items[1]['qty'])->toBe(1);
    expect((float) $order->items[1]['price'])->toBe(25.0);
});

test('advanced per load orders add the base service amount and priced add ons', function () {
    $this->tenant->features = ['advanced_pricing'];
    $this->tenant->save();

    $service = $this->tenant->run(function (): Service {
        return Service::create([
            'name' => 'Commercial Load',
            'price_type' => 'per_load',
            'price' => 120,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    });

    ($this->loginOwner)();

    $this->post(($this->tenantUrl)('/orders'), [
        'customer_id' => $this->customerId,
        'service_id' => $service->id,
        'status' => 'received',
        'items' => [
            ['name' => 'Fabric Conditioner', 'qty' => 2, 'price' => 15],
        ],
        'total_amount' => 5,
    ])->assertRedirect(route('tenant.orders.index', absolute: false));

    $order = $this->tenant->run(fn (): ?Order => Order::query()->latest('id')->first());

    expect($order)->not->toBeNull();
    expect($order->weight)->toBeNull();
    expect((float) $order->total_amount)->toBe(150.0);
});
