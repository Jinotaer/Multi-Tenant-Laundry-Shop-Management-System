<?php

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\CustomerSetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

beforeEach(function () {
    $tenantKey = 'invite'.Str::lower(Str::random(8));

    $this->tenantDomain = $tenantKey.'.localhost';
    $this->tenant = Tenant::create([
        'id' => $tenantKey,
        'is_enabled' => true,
        'is_paid' => true,
        'data' => ['shop_name' => 'Invite Shop'],
    ]);

    $this->tenant->domains()->create([
        'domain' => $this->tenantDomain,
    ]);

    $this->tenantUrl = fn (string $path): string => "http://{$this->tenantDomain}{$path}";

    $this->tenant->run(function (): void {
        User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
    });
});

afterEach(function () {
    tenancy()->end();

    if (isset($this->tenant) && $this->tenant->exists) {
        $this->tenant->delete();
    }
});

test('owner can invite a customer to set their password after account creation', function () {
    Notification::fake();

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'owner@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->post(($this->tenantUrl)('/customers'), [
        'name' => 'Invited Customer',
        'phone' => '09171234567',
        'email' => 'customer@example.com',
    ])
        ->assertRedirect(route('tenant.customers.index', absolute: false))
        ->assertSessionHas(
            'success',
            'Customer added successfully. A password setup email was sent to the customer.',
        );

    $customer = $this->tenant->run(function (): Customer {
        $customer = Customer::query()
            ->where('email', 'customer@example.com')
            ->firstOrFail();

        expect($customer->password)->toBeNull();
        expect(User::query()->where('email', 'customer@example.com')->exists())->toBeFalse();

        return $customer;
    });

    $this->post(($this->tenantUrl)('/logout'))
        ->assertRedirect(route('tenant.login', absolute: false));

    Notification::assertSentTo(
        $customer,
        CustomerSetPasswordNotification::class,
        function (CustomerSetPasswordNotification $notification) use ($customer): bool {
            $this->get($notification->url)
                ->assertOk()
                ->assertSee('Set your customer password');

            $this->post(($this->tenantUrl)('/reset-password'), [
                'token' => $notification->token,
                'email' => $customer->email,
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
            ])
                ->assertRedirect(route('tenant.login', absolute: false))
                ->assertSessionHas('status');

            return true;
        },
    );

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'customer@example.com',
        'password' => 'SecurePass123!',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));
});

test('customers without an email are created without a password setup email', function () {
    Notification::fake();

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'owner@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->post(($this->tenantUrl)('/customers'), [
        'name' => 'Walk-in Customer',
        'phone' => '09170000000',
    ])
        ->assertRedirect(route('tenant.customers.index', absolute: false))
        ->assertSessionHas('success', 'Customer added successfully.');

    $this->tenant->run(function (): void {
        $customer = Customer::query()
            ->where('name', 'Walk-in Customer')
            ->firstOrFail();

        expect($customer->email)->toBeNull();
        expect($customer->password)->toBeNull();
    });

    Notification::assertNothingSent();
});

test('customer create route redirects to the index modal and hides notes from the create form', function () {
    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'owner@example.com',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/customers'))
        ->assertOk()
        ->assertSee('customer-create-modal', false)
        ->assertSee('Customer login setup')
        ->assertSee('Save Customer')
        ->assertSee('dark:bg-slate-800/80', false)
        ->assertSee('bg-indigo-100', false)
        ->assertDontSee('bg-indigo-50', false)
        ->assertDontSee('Notes');

    $this->get(($this->tenantUrl)('/customers/create'))
        ->assertRedirect(($this->tenantUrl)('/customers?create=1'));
});
