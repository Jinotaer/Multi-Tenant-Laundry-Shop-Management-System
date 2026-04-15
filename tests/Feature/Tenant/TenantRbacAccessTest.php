<?php

use App\Models\AppRelease;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $tenantKey = 'tenantrbac'.Str::lower(Str::random(8));

    $this->tenantDomain = $tenantKey.'.localhost';
    $this->tenant = Tenant::create([
        'id' => $tenantKey,
        'is_enabled' => true,
        'is_paid' => true,
        'data' => ['shop_name' => 'Tenant RBAC Shop'],
    ]);

    $this->tenant->domains()->create(['domain' => $this->tenantDomain]);

    $this->tenant->run(function (): void {
        Permission::ensureDefaultsExist();

        User::create([
            'name' => 'Owner User',
            'email' => 'owner@tenant-rbac.test',
            'password' => 'password',
            'role' => 'owner',
        ]);
    });

    $this->tenantUrl = fn (string $path): string => "http://{$this->tenantDomain}{$path}";
});

afterEach(function (): void {
    tenancy()->end();

    if (isset($this->tenant) && $this->tenant->exists) {
        $this->tenant->delete();
    }
});

test('staff cannot access owner update center routes', function (): void {
    $this->tenant->run(function (): void {
        User::create([
            'name' => 'Update Staff',
            'email' => 'update-staff@tenant-rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);
    });

    $release = AppRelease::create([
        'version_tag' => 'v9.9.9',
        'name' => 'RBAC test release',
        'body' => 'Testing RBAC.',
        'published_at' => now(),
    ]);

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'update-staff@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/updates'))
        ->assertForbidden();

    $this->post(($this->tenantUrl)("/updates/{$release->id}/apply"))
        ->assertForbidden();

    $this->post(($this->tenantUrl)("/updates/{$release->id}/rollback"))
        ->assertForbidden();
});

test('owner can access update center', function (): void {
    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'owner@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/updates'))
        ->assertOk()
        ->assertSee('Update Center');
});

test('owner can access analytics page when analytics feature is enabled', function (): void {
    $this->tenant->update([
        'features' => ['analytics_dashboard'],
    ]);

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'owner@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/analytics'))
        ->assertOk()
        ->assertSee('Analytics')
        ->assertSee('Revenue Timeline')
        ->assertSee('Order Status Mix');
});

test('staff index works before role tables are migrated', function (): void {
    $this->tenant->run(function (): void {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');

        $staff = User::create([
            'name' => 'Migrated Later Staff',
            'email' => 'migrated-later@tenant-rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);

        $permissionId = Permission::query()
            ->where('key', 'staff.view')
            ->value('id');

        $staff->permissions()->sync([
            $permissionId => ['granted_by' => null],
        ]);
    });

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'migrated-later@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/staff'))
        ->assertOk()
        ->assertSee('Migrated Later Staff')
        ->assertDontSee('Roles');
});

test('custom manager roles remain available and legacy permissions are migrated', function (): void {
    $this->tenant->run(function (): void {
        $staff = User::create([
            'name' => 'Legacy Role Staff',
            'email' => 'legacy-role@tenant-rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);

        $managerRole = Role::query()->create([
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => 'Legacy manager role.',
        ]);

        $servicesPermissionId = Permission::query()->create([
            'key' => 'services.manage',
            'label' => 'Manage Services',
            'module' => 'services',
        ])->id;

        $managerRole->permissions()->sync([$servicesPermissionId]);
        $staff->roles()->syncWithoutDetaching([
            $managerRole->id => ['assigned_by' => null],
        ]);

        Permission::ensureDefaultsExist();

        expect(Role::query()->where('slug', 'manager')->exists())->toBeTrue();
        expect(Permission::query()->where('key', 'services.manage')->exists())->toBeFalse();
        expect($staff->fresh()->hasRole('staff'))->toBeTrue();
        expect($staff->fresh()->hasRole('manager'))->toBeTrue();
        expect($staff->fresh()->hasPermission('services.manage'))->toBeFalse();
        expect($staff->fresh()->hasPermission('services.view'))->toBeTrue();
    });
});

test('staff and roles pages render separate interfaces', function (): void {
    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'owner@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/staff'))
        ->assertOk()
        ->assertSee('Staff Management')
        ->assertSee('Add Staff')
        ->assertSee('staff-create-modal', false)
        ->assertSee('Select Roles')
        ->assertDontSee('Privileges')
        ->assertDontSee('No privileges assigned')
        ->assertDontSee('Role Library');

    $this->get(($this->tenantUrl)('/staff?tab=roles'))
        ->assertOk()
        ->assertSee('Staff Management')
        ->assertDontSee('Role Library');

    $this->get(($this->tenantUrl)('/roles'))
        ->assertOk()
        ->assertSee('Roles')
        ->assertSee('Add Role')
        ->assertDontSee('Select All')
        ->assertSee('toggleModule', false)
        ->assertSee('role-create-modal', false)
        ->assertSee('Select Permissions')
        ->assertDontSee('Staff Directory');
});

test('staff with roles view permission can access roles page without role creation controls', function (): void {
    $this->tenant->run(function (): void {
        $staff = User::create([
            'name' => 'Roles Viewer',
            'email' => 'roles-viewer@tenant-rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);

        $rolesViewPermissionId = Permission::query()
            ->where('key', 'roles.view')
            ->value('id');

        $staff->permissions()->sync([
            $rolesViewPermissionId => ['granted_by' => null],
        ]);
    });

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'roles-viewer@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/roles'))
        ->assertOk()
        ->assertSee('Roles')
        ->assertDontSee('Add Role');
});

test('owner can create a role with permissions and assign it to staff', function (): void {
    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'owner@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/roles'))
        ->assertOk()
        ->assertSee('Add Role')
        ->assertSee('Select Permissions');

    $this->post(($this->tenantUrl)('/roles'), [
        'name' => 'Cashier',
        'description' => 'Can view billing only.',
        'permissions' => ['billing.view'],
    ])->assertRedirect(route('tenant.roles.index', absolute: false));

    $this->tenant->run(function (): void {
        $role = Role::query()->where('slug', 'cashier')->firstOrFail();

        expect($role->permissions()->pluck('key')->all())
            ->toContain('billing.view');
    });

    $this->post(($this->tenantUrl)('/staff'), [
        'name' => 'Cashier Staff',
        'email' => 'cashier-staff@tenant-rbac.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'roles' => ['cashier'],
    ])->assertRedirect(route('tenant.staff.index', absolute: false));

    $this->tenant->run(function (): void {
        $staff = User::query()
            ->where('email', 'cashier-staff@tenant-rbac.test')
            ->firstOrFail();

        expect($staff->hasRole('cashier'))->toBeTrue();
        expect($staff->hasPermission('billing.view'))->toBeTrue();
        expect($staff->hasPermission('services.view'))->toBeFalse();
    });
});

test('staff edit route redirects back to the staff page modal', function (): void {
    $staffId = $this->tenant->run(function (): int {
        return User::create([
            'name' => 'Redirect Staff',
            'email' => 'redirect-staff@tenant-rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ])->id;
    });

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'owner@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)("/staff/{$staffId}/edit"))
        ->assertRedirect(route('tenant.staff.index', ['edit' => $staffId], false));
});

test('owner can create manager as a custom role name', function (): void {
    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'owner@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->post(($this->tenantUrl)('/roles'), [
        'name' => 'Manager',
        'description' => 'Custom manager role.',
        'permissions' => ['billing.view'],
    ])->assertRedirect(route('tenant.roles.index', absolute: false));

    $this->tenant->run(function (): void {
        $role = Role::query()->where('slug', 'manager')->firstOrFail();

        expect($role->name)->toBe('Manager');
        expect($role->permissions()->pluck('key')->all())
            ->toContain('billing.view');
    });
});
test('non-owner staff cannot assign rbac roles to created staff', function (): void {
    $this->tenant->run(function (): void {
        $cashierRole = Role::query()->create([
            'name' => 'Cashier',
            'slug' => 'cashier',
            'description' => 'Can view billing only.',
        ]);

        $cashierRole->permissions()->sync([
            Permission::query()->where('key', 'billing.view')->value('id'),
        ]);

        $creator = User::create([
            'name' => 'Staff Creator',
            'email' => 'staff-creator@tenant-rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);

        $permissionId = Permission::query()
            ->where('key', 'staff.create')
            ->value('id');

        $creator->permissions()->sync([
            $permissionId => ['granted_by' => null],
        ]);
    });

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'staff-creator@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->post(($this->tenantUrl)('/staff'), [
        'name' => 'Unauthorized Role Staff',
        'email' => 'unauthorized-role@tenant-rbac.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'roles' => ['cashier'],
    ])->assertRedirect(route('tenant.staff.index', absolute: false));

    $this->tenant->run(function (): void {
        $staff = User::query()
            ->where('email', 'unauthorized-role@tenant-rbac.test')
            ->firstOrFail();

        expect($staff->hasRole('staff'))->toBeTrue();
        expect($staff->hasRole('cashier'))->toBeFalse();
        expect($staff->hasPermission('billing.view'))->toBeFalse();
    });
});

test('staff search remains scoped to staff users', function (): void {
    $this->tenant->run(function (): void {
        $staff = User::create([
            'name' => 'Staff Viewer',
            'email' => 'staff-viewer@tenant-rbac.test',
            'password' => 'password',
            'role' => 'staff',
        ]);

        $permissionId = Permission::query()
            ->where('key', 'staff.view')
            ->value('id');

        $staff->permissions()->sync([
            $permissionId => ['granted_by' => null],
        ]);
    });

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'staff-viewer@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/staff?search=owner@tenant-rbac.test'))
        ->assertOk()
        ->assertDontSee('Owner User');
});
