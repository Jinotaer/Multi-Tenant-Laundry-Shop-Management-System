<?php

use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $tenantKey = 'rbac'.Str::lower(Str::random(8));

    $this->tenantDomain = $tenantKey.'.localhost';
    $this->tenant = Tenant::create([
        'id' => $tenantKey,
        'is_enabled' => true,
        'is_paid' => true,
        'data' => ['shop_name' => 'RBAC Shop'],
    ]);

    $this->tenant->domains()->create(['domain' => $this->tenantDomain]);

    $this->tenant->run(function (): void {
        Permission::ensureDefaultsExist();

        User::create([
            'name' => 'Owner User',
            'email' => 'owner@rbac.test',
            'password' => 'password',
            'role' => 'owner',
        ]);
    });

    $this->tenantUrl = fn (string $path): string => "http://{$this->tenantDomain}{$path}";
    $this->loginOwner = function (): void {
        $this->post(($this->tenantUrl)('/login'), [
            'email' => 'owner@rbac.test',
            'password' => 'password',
        ])->assertRedirect(route('tenant.dashboard', absolute: false));
    };
});

afterEach(function (): void {
    tenancy()->end();

    if (isset($this->tenant) && $this->tenant->exists) {
        $this->tenant->delete();
    }
});

test('owner can assign privileges when creating staff', function (): void {
    ($this->loginOwner)();

    $this->post(($this->tenantUrl)('/staff'), [
        'name' => 'Assigned Staff',
        'email' => 'assigned-staff@rbac.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'permissions' => ['staff.view', 'customers.view'],
    ])->assertRedirect(route('tenant.staff.index', absolute: false));

    $this->tenant->run(function (): void {
        $staff = User::query()->where('email', 'assigned-staff@rbac.test')->first();

        expect($staff)->not->toBeNull();
        expect($staff->hasPermission('staff.view'))->toBeTrue();
        expect($staff->hasPermission('customers.view'))->toBeTrue();
    });
});

test('staff without staff view privilege cannot access staff index', function (): void {
    $this->tenant->run(function (): void {
        User::create([
            'name' => 'Restricted Staff',
            'email' => 'restricted-staff@rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);
    });

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'restricted-staff@rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/staff'))
        ->assertForbidden();
});

test('staff manager cannot grant permissions manage privilege', function (): void {
    $this->tenant->run(function (): void {
        $manager = User::create([
            'name' => 'Manager Staff',
            'email' => 'manager-staff@rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);

        $target = User::create([
            'name' => 'Target Staff',
            'email' => 'target-staff@rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);

        $permissionIds = Permission::query()
            ->whereIn('key', ['permissions.manage', 'staff.update', 'customers.view'])
            ->pluck('id')
            ->all();

        $syncPayload = [];

        foreach ($permissionIds as $permissionId) {
            $syncPayload[$permissionId] = ['granted_by' => null];
        }

        $manager->permissions()->sync($syncPayload);

        expect($target->hasPermission('permissions.manage'))->toBeFalse();
    });

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'manager-staff@rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $targetId = $this->tenant->run(fn (): int => User::query()->where('email', 'target-staff@rbac.test')->value('id'));

    $this->put(($this->tenantUrl)("/staff/{$targetId}"), [
        'name' => 'Target Staff Updated',
        'email' => 'target-staff@rbac.test',
        'password' => '',
        'password_confirmation' => '',
        'permissions' => ['permissions.manage', 'customers.view'],
    ])->assertRedirect(route('tenant.staff.index', absolute: false));

    $this->tenant->run(function (): void {
        $target = User::query()->where('email', 'target-staff@rbac.test')->firstOrFail();

        expect($target->hasPermission('customers.view'))->toBeTrue();
        expect($target->hasPermission('permissions.manage'))->toBeFalse();
    });
});

test('staff with services and billing privileges can access management routes', function (): void {
    $this->tenant->run(function (): void {
        $staff = User::create([
            'name' => 'Operations Staff',
            'email' => 'ops-staff@rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);

        $permissionIds = Permission::query()
            ->whereIn('key', ['services.manage', 'billing.view'])
            ->pluck('id')
            ->all();

        $syncPayload = [];

        foreach ($permissionIds as $permissionId) {
            $syncPayload[$permissionId] = ['granted_by' => null];
        }

        $staff->permissions()->sync($syncPayload);
    });

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'ops-staff@rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/services'))
        ->assertOk();

    $this->get(($this->tenantUrl)('/billing'))
        ->assertOk();
});

test('staff can access orders by default without order privileges', function (): void {
    $this->tenant->run(function (): void {
        User::create([
            'name' => 'Order Default Staff',
            'email' => 'order-default@rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);
    });

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'order-default@rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/orders'))
        ->assertOk();
});
