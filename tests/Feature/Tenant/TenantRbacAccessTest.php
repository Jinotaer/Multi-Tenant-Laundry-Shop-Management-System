<?php

use App\Models\AppRelease;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CodeDeploymentService;
use App\Services\TenantBackupService;
use App\Services\TenantMigrationService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery\MockInterface;

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

test('owner sees rollback action for a previously used version', function (): void {
    $previousRelease = AppRelease::create([
        'version_tag' => 'v1.0.0',
        'name' => 'Stable release',
        'body' => 'Rollback candidate.',
        'published_at' => now()->subDays(7),
    ]);

    $currentRelease = AppRelease::create([
        'version_tag' => 'v1.0.1',
        'name' => 'Current release',
        'body' => 'Active version.',
        'published_at' => now()->subDay(),
    ]);

    $this->tenant->updates()->create([
        'app_release_id' => $previousRelease->id,
        'status' => 'updated',
        'is_current' => false,
        'action_taken_at' => now()->subDays(6),
    ]);

    $this->tenant->updates()->create([
        'app_release_id' => $currentRelease->id,
        'status' => 'updated',
        'is_current' => true,
        'action_taken_at' => now()->subDay(),
    ]);

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'owner@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/updates'))
        ->assertOk()
        ->assertSee('Rollback')
        ->assertSee("/updates/{$previousRelease->id}/rollback", false);
});

test('update center keeps premium feature navigation visible', function (): void {
    $this->tenant->update([
        'features' => ['expense_tracking', 'reports', 'analytics_dashboard'],
    ]);

    $release = AppRelease::create([
        'version_tag' => 'v1.0.4',
        'name' => 'Current release',
        'body' => 'Current version.',
        'published_at' => now()->subDay(),
    ]);

    $this->tenant->updates()->create([
        'app_release_id' => $release->id,
        'status' => 'updated',
        'is_current' => true,
        'action_taken_at' => now()->subDay(),
    ]);

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'owner@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/updates'))
        ->assertOk()
        ->assertSee('Expenses')
        ->assertSee('Reports')
        ->assertSee('Analytics');
});

test('owner sees rollback options for older releases even without prior tenant history', function (): void {
    $olderRelease = AppRelease::create([
        'version_tag' => 'v1.0.3',
        'name' => 'Previous release',
        'body' => 'Rollback target.',
        'published_at' => now()->subDays(2),
    ]);

    $currentRelease = AppRelease::create([
        'version_tag' => 'v1.0.4',
        'name' => 'Current release',
        'body' => 'Current version.',
        'published_at' => now()->subDay(),
    ]);

    $this->tenant->updates()->create([
        'app_release_id' => $currentRelease->id,
        'status' => 'updated',
        'is_current' => true,
        'action_taken_at' => now()->subDay(),
    ]);

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'owner@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->get(($this->tenantUrl)('/updates'))
        ->assertOk()
        ->assertSee('Available Rollbacks')
        ->assertSee($olderRelease->version_tag)
        ->assertSee('Rollback')
        ->assertSee('never used this version before', false);
});

test('rollback deploys code when automatic deployment is enabled', function (): void {
    config()->set('updates.auto_deploy_code', true);

    $previousRelease = AppRelease::create([
        'version_tag' => 'v1.0.0',
        'name' => 'Stable release',
        'body' => 'Rollback candidate.',
        'published_at' => now()->subDays(7),
    ]);

    $currentRelease = AppRelease::create([
        'version_tag' => 'v1.0.1',
        'name' => 'Current release',
        'body' => 'Active version.',
        'published_at' => now()->subDay(),
    ]);

    $this->tenant->updates()->create([
        'app_release_id' => $previousRelease->id,
        'status' => 'updated',
        'is_current' => false,
        'action_taken_at' => now()->subDays(6),
    ]);

    $this->tenant->updates()->create([
        'app_release_id' => $currentRelease->id,
        'status' => 'updated',
        'is_current' => true,
        'action_taken_at' => now()->subDay(),
    ]);

    $this->mock(TenantBackupService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('createBackup')
            ->once()
            ->andReturn([
                'success' => true,
                'backup_path' => storage_path('framework/testing/rollback-test.zip'),
                'backup_name' => 'rollback-test',
                'size' => 1024,
            ]);

        $mock->shouldNotReceive('restoreBackup');
    });

    $this->mock(CodeDeploymentService::class, function (MockInterface $mock) use ($previousRelease): void {
        $mock->shouldReceive('deployFromGitHub')
            ->once()
            ->with($previousRelease->version_tag)
            ->andReturn([
                'success' => true,
                'version' => $previousRelease->version_tag,
                'backup_path' => storage_path('framework/testing/code-backup'),
            ]);

        $mock->shouldNotReceive('rollbackCode');
    });

    $this->mock(TenantMigrationService::class, function (MockInterface $mock) use ($currentRelease, $previousRelease): void {
        $mock->shouldReceive('rollbackMigrationsForVersion')
            ->once()
            ->withArgs(function ($tenant, $fromVersion, $toVersion) use ($currentRelease, $previousRelease): bool {
                return $tenant->id === $this->tenant->id
                    && $fromVersion === $currentRelease->version_tag
                    && $toVersion === $previousRelease->version_tag;
            })
            ->andReturn([
                'success' => true,
                'migrations_rolled_back' => 1,
            ]);
    });

    $this->post(($this->tenantUrl)('/login'), [
        'email' => 'owner@tenant-rbac.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', absolute: false));

    $this->from(($this->tenantUrl)('/updates'))
        ->post(($this->tenantUrl)("/updates/{$previousRelease->id}/rollback"))
        ->assertRedirect(($this->tenantUrl)('/updates'))
        ->assertSessionHas('success');

    $this->tenant->refresh();

    expect($this->tenant->updates()->where('app_release_id', $previousRelease->id)->first()?->is_current)->toBeTrue();
    expect($this->tenant->updates()->where('app_release_id', $currentRelease->id)->first()?->is_current)->toBeFalse();
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
