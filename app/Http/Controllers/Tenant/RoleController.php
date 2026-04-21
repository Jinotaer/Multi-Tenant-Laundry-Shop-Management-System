<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\RoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        Permission::ensureDefaultsExist();

        $actor = $request->user();
        $hasRoleTables = $this->hasRoleTables();
        $rolePermissionsByModule = collect();
        $roles = collect();
        $configuredRoleSlugs = [];
        $editableRoleIds = collect();
        $deletableRoleIds = collect();
        $canCreateRoles = $actor !== null && ($actor->isOwner() || $actor->hasPermission('roles.create'));
        $canUpdateRoles = $actor !== null && ($actor->isOwner() || $actor->hasPermission('roles.update'));
        $canDeleteRoles = $actor !== null && ($actor->isOwner() || $actor->hasPermission('roles.delete'));

        if ($hasRoleTables) {
            Role::ensureDefaultsExist();

            $permissions = Permission::query()
                ->orderBy('module')
                ->get()
                ->sortBy(function ($permission) {
                    // Custom sort order: view first, then create, update, delete, others
                    $action = explode('.', $permission->key)[1] ?? '';
                    $order = [
                        'view' => 1,
                        'create' => 2,
                        'update' => 3,
                        'delete' => 4,
                    ];
                    return ($order[$action] ?? 99) . $permission->key;
                })
                ->values();

            // Filter permissions based on subscription features
            $permissions = $this->filterPermissionsBySubscriptionFeatures($permissions);

            if ($actor !== null && ! $actor->isOwner()) {
                $permissions = $permissions
                    ->filter(fn (Permission $permission): bool => $actor->canGrantPermission($permission->key))
                    ->values();
            }

            $rolePermissionsByModule = $permissions->groupBy('module');
            $roles = Role::query()
                ->whereNotIn('slug', ['owner', 'staff', 'customer'])
                ->with(['permissions', 'users'])
                ->orderBy('name')
                ->get();
            $configuredRoleSlugs = Role::definitions()
                ->pluck('slug')
                ->all();

            if ($actor !== null) {
                $editableRoleIds = $roles
                    ->filter(fn (Role $role): bool => $this->rolePermissionsAreManageableBy($actor, $role))
                    ->pluck('id');

                $deletableRoleIds = $editableRoleIds;
            }
        }

        return view('tenant.roles.index', compact(
            'hasRoleTables',
            'rolePermissionsByModule',
            'roles',
            'configuredRoleSlugs',
            'editableRoleIds',
            'deletableRoleIds',
            'canCreateRoles',
            'canUpdateRoles',
            'canDeleteRoles',
        ));
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        abort_unless($this->hasRoleTables(), 404);

        Permission::ensureDefaultsExist();

        $validated = $request->validated();

        $role = Role::query()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        $this->syncPermissions($role, $validated['permissions'] ?? [], $request->user());

        return redirect()
            ->route('tenant.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        abort_unless($this->canManageRole($role), 404);
        abort_unless($this->rolePermissionsAreManageableBy($request->user(), $role), 403);

        $validated = $request->validated();

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $this->syncPermissions($role, $validated['permissions'] ?? [], $request->user());

        return redirect()
            ->route('tenant.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        abort_unless($this->canManageRole($role), 404);
        abort_unless($this->rolePermissionsAreManageableBy($request->user(), $role), 403);

        if ($this->isConfiguredRole($role) || $role->users()->exists()) {
            return redirect()
                ->route('tenant.roles.index')
                ->with('error', 'This role cannot be removed while it is protected or assigned to staff.');
        }

        $role->delete();

        return redirect()
            ->route('tenant.roles.index')
            ->with('success', 'Role removed successfully.');
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    private function syncPermissions(Role $role, array $permissionKeys, ?User $actor): void
    {
        if ($actor === null) {
            abort(403, 'Unauthorized.');
        }

        $allowedPermissionKeys = $this->allowedPermissionKeysForActor($actor, $permissionKeys);

        $permissionIds = Permission::query()
            ->whereIn('key', $allowedPermissionKeys)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
    }

    private function canManageRole(Role $role): bool
    {
        return $this->hasRoleTables()
            && ! in_array($role->slug, ['owner', 'staff', 'customer'], true);
    }

    private function isConfiguredRole(Role $role): bool
    {
        return Role::definitions()
            ->pluck('slug')
            ->contains($role->slug);
    }

    private function hasRoleTables(): bool
    {
        return Schema::hasTable('roles')
            && Schema::hasTable('role_user')
            && Schema::hasTable('permission_role');
    }

    /**
     * @param  list<string>  $requestedPermissionKeys
     * @return array<int, string>
     */
    private function allowedPermissionKeysForActor(User $actor, array $requestedPermissionKeys): array
    {
        $requested = collect($requestedPermissionKeys)
            ->filter(fn ($key): bool => is_string($key))
            ->values();

        if ($actor->isOwner()) {
            return $requested->all();
        }

        return $requested
            ->filter(fn (string $key): bool => $actor->canGrantPermission($key))
            ->values()
            ->all();
    }

    private function rolePermissionsAreManageableBy(?User $actor, Role $role): bool
    {
        if ($actor === null) {
            return false;
        }

        return $actor->canManageRolePermissions($role);
    }

    /**
     * Filter permissions based on tenant's subscription features.
     *
     * @param  \Illuminate\Support\Collection<int, Permission>  $permissions
     * @return \Illuminate\Support\Collection<int, Permission>
     */
    private function filterPermissionsBySubscriptionFeatures($permissions)
    {
        $tenant = tenant();
        
        if (!$tenant) {
            return $permissions;
        }

        // Map modules to required features
        $moduleFeatureMap = [
            'expenses' => 'expense_tracking',
            'inventory' => 'inventory_management',
            'reports' => 'reports',
            'analytics' => 'analytics_dashboard',
        ];

        return $permissions->filter(function ($permission) use ($tenant, $moduleFeatureMap) {
            // Core modules are always available
            $coreModules = ['dashboard', 'orders', 'customers', 'staff', 'roles', 'services', 'billing'];
            
            if (in_array($permission->module, $coreModules, true)) {
                return true;
            }

            // Check if module requires a feature
            if (isset($moduleFeatureMap[$permission->module])) {
                return $tenant->hasFeature($moduleFeatureMap[$permission->module]);
            }

            // Allow by default if not mapped
            return true;
        });
    }
}
