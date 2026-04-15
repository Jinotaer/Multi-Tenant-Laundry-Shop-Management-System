<?php

use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Role;
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

test('owner can update staff through the staff modal flow and assign roles', function (): void {
    $staffId = $this->tenant->run(function (): int {
        $cashierRole = Role::query()->create([
            'name' => 'Cashier',
            'slug' => 'cashier',
            'description' => 'Can view billing only.',
        ]);

        $cashierRole->permissions()->sync([
            Permission::query()->where('key', 'billing.view')->value('id'),
        ]);

        return User::create([
            'name' => 'Modal Staff',
            'email' => 'modal-staff@rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ])->id;
    });

    ($this->loginOwner)();

    $this->from(($this->tenantUrl)("/staff?edit={$staffId}"))
        ->put(($this->tenantUrl)("/staff/{$staffId}"), [
            'name' => 'Modal Staff Updated',
            'email' => 'modal-staff@rbac.test',
            'password' => '',
            'password_confirmation' => '',
            'roles' => ['cashier'],
        ])->assertRedirect(route('tenant.staff.index', absolute: false));

    $this->tenant->run(function (): void {
        $staff = User::query()->where('email', 'modal-staff@rbac.test')->firstOrFail();

        expect($staff->name)->toBe('Modal Staff Updated');
        expect($staff->hasRole('cashier'))->toBeTrue();
        expect($staff->hasPermission('billing.view'))->toBeTrue();
    });
});

test('users are attached to their matching role records when created', function (): void {
    $this->tenant->run(function (): void {
        $owner = User::query()->where('email', 'owner@rbac.test')->firstOrFail();

        expect(Role::query()->pluck('slug')->all())
            ->toContain('owner', 'staff', 'customer');
        expect($owner->roles()->pluck('slug')->all())
            ->toContain('owner');
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

test('staff create and update ignore direct permission payloads', function (): void {
    $this->tenant->run(function (): void {
        $target = User::create([
            'name' => 'Target Staff',
            'email' => 'target-staff@rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);
    });

    ($this->loginOwner)();

    $this->post(($this->tenantUrl)('/staff'), [
        'name' => 'Created Without Direct Permissions',
        'email' => 'created-no-direct@rbac.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'permissions' => ['customers.view', 'billing.view'],
    ])->assertRedirect(route('tenant.staff.index', absolute: false));

    $targetId = $this->tenant->run(fn (): int => User::query()->where('email', 'target-staff@rbac.test')->value('id'));

    $this->from(($this->tenantUrl)("/staff?edit={$targetId}"))
        ->put(($this->tenantUrl)("/staff/{$targetId}"), [
            'name' => 'Target Staff Updated',
            'email' => 'target-staff@rbac.test',
            'password' => '',
            'password_confirmation' => '',
            'permissions' => ['customers.view', 'billing.view'],
        ])->assertRedirect(route('tenant.staff.index', absolute: false));

    $this->tenant->run(function (): void {
        $target = User::query()->where('email', 'target-staff@rbac.test')->firstOrFail();
        $created = User::query()->where('email', 'created-no-direct@rbac.test')->firstOrFail();

        expect($created->permissions()->exists())->toBeFalse();
        expect($created->hasPermission('customers.view'))->toBeFalse();
        expect($target->permissions()->exists())->toBeFalse();
        expect($target->hasPermission('customers.view'))->toBeFalse();
        expect($target->hasPermission('billing.view'))->toBeFalse();
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
            ->whereIn('key', ['services.view', 'billing.view'])
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

test('staff with role assignment permissions can assign manageable custom roles', function (): void {
    $this->tenant->run(function (): void {
        $cashierRole = Role::query()->create([
            'name' => 'Cashier',
            'slug' => 'cashier',
            'description' => 'Can view billing only.',
        ]);

        $cashierRole->permissions()->sync([
            Permission::query()->where('key', 'billing.view')->value('id'),
        ]);

        $staff = User::create([
            'name' => 'Role Assigner',
            'email' => 'role-assigner@rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);

        $permissionIds = Permission::query()
            ->whereIn('key', ['staff.create', 'staff.assign_roles', 'staff.assign_permissions', 'billing.view'])
            ->pluck('id')
            ->all();

        $syncPayload = [];

        foreach ($permissionIds as $permissionId) {
            $syncPayload[$permissionId] = ['granted_by' => null];
        }

        $staff->permissions()->sync($syncPayload);
    });

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'role-assigner@rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->post(($this->tenantUrl)('/staff'), [
        'name' => 'Assigned By Staff',
        'email' => 'assigned-by-staff@rbac.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'roles' => ['cashier'],
    ])->assertRedirect(route('tenant.staff.index', absolute: false));

    $this->tenant->run(function (): void {
        $staff = User::query()->where('email', 'assigned-by-staff@rbac.test')->firstOrFail();

        expect($staff->hasRole('cashier'))->toBeTrue();
        expect($staff->hasPermission('billing.view'))->toBeTrue();
    });
});

test('billing download requires billing download permission', function (): void {
    $invoice = Invoice::create([
        'invoice_number' => 'INV-RBAC-1001',
        'tenant_id' => $this->tenant->id,
        'billing_name' => 'RBAC Billing',
        'billing_email' => 'billing@rbac.test',
        'subtotal' => 999,
        'tax_rate' => 0,
        'tax_amount' => 0,
        'total' => 999,
        'currency' => 'PHP',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->toDateString(),
        'status' => Invoice::STATUS_PAID,
    ]);

    $this->tenant->run(function (): void {
        $staff = User::create([
            'name' => 'Billing Viewer',
            'email' => 'billing-viewer@rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);

        $billingViewPermissionId = Permission::query()
            ->where('key', 'billing.view')
            ->value('id');

        $staff->permissions()->sync([
            $billingViewPermissionId => ['granted_by' => null],
        ]);
    });

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'billing-viewer@rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/billing'))
        ->assertOk();

    $this->get(($this->tenantUrl)("/billing/{$invoice->id}/download"))
        ->assertForbidden();
});

test('staff can inherit permissions from an assigned role', function (): void {
    $this->tenant->run(function (): void {
        $staff = User::create([
            'name' => 'Role Based Staff',
            'email' => 'role-staff@rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);

        $staffRole = Role::query()
            ->where('slug', 'staff')
            ->firstOrFail();

        $billingPermissionId = Permission::query()
            ->where('key', 'billing.view')
            ->value('id');

        $staffRole->permissions()->syncWithoutDetaching([$billingPermissionId]);

        expect($staff->fresh()->hasPermission('billing.view'))->toBeTrue();
    });

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'role-staff@rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

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
