<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StaffRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StaffController extends Controller
{
    /**
     * Display a listing of all staff members.
     */
    public function index(Request $request): View
    {
        Permission::ensureDefaultsExist();
        $actor = $request->user();
        $hasRoleTables = $this->hasRoleTables();

        $staffQuery = User::query()
            ->where('role', 'staff')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });

        if ($hasRoleTables) {
            $staffQuery->with('roles');
        }

        $staff = $staffQuery
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $planLimitService = new PlanLimitService(tenant());
        $canAddStaff = $planLimitService->canAddStaff(User::where('role', 'staff')->count());
        $canCreateStaff = $actor !== null && ($actor->isOwner() || $actor->hasPermission('staff.create'));
        $canUpdateStaff = $actor !== null && ($actor->isOwner() || $actor->hasPermission('staff.update'));
        $canDeleteStaff = $actor !== null && ($actor->isOwner() || $actor->hasPermission('staff.delete'));
        $canManageRoles = $hasRoleTables
            && $actor !== null
            && ($actor->isOwner() || $actor->hasPermission('staff.assign_roles'));
        $assignableRoles = collect();
        $editingStaff = null;

        if ($canManageRoles) {
            $assignableRoles = $this->assignableRoles($actor);
        }

        $requestedStaffEditId = $request->integer('edit');

        if ($requestedStaffEditId > 0 && $canUpdateStaff) {
            $editingStaffQuery = User::query()
                ->whereKey($requestedStaffEditId)
                ->where('role', 'staff');

            if ($hasRoleTables) {
                $editingStaffQuery->with('roles');
            }

            $editingStaff = $editingStaffQuery->first();
        }

        return view('tenant.staff.index', compact(
            'staff',
            'canAddStaff',
            'canCreateStaff',
            'canUpdateStaff',
            'canDeleteStaff',
            'canManageRoles',
            'assignableRoles',
            'hasRoleTables',
            'editingStaff',
        ));
    }

    /**
     * Show the form for creating a new staff member.
     */
    public function create(): View
    {
        Permission::ensureDefaultsExist();
        $actor = request()->user();
        $hasRoleTables = $this->hasRoleTables();
        $canManageRoles = $hasRoleTables
            && $actor !== null
            && ($actor->isOwner() || $actor->hasPermission('staff.assign_roles'));

        $planLimitService = new PlanLimitService(tenant());
        $canAddStaff = $planLimitService->canAddStaff(User::where('role', 'staff')->count());

        if (! $canAddStaff) {
            return view('tenant.staff.limit-reached');
        }

        return view('tenant.staff.create', [
            'assignableRoles' => $this->assignableRoles($actor),
            'canManageRoles' => $canManageRoles,
        ]);
    }

    /**
     * Store a newly created staff member.
     */
    public function store(StaffRequest $request): RedirectResponse
    {
        Permission::ensureDefaultsExist();

        $planLimitService = new PlanLimitService(tenant());

        if (! $planLimitService->canAddStaff(User::where('role', 'staff')->count())) {
            return redirect()->route('tenant.staff.index')
                ->with('error', 'Staff limit reached for your current plan. Please upgrade.');
        }

        $staff = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'role' => 'staff',
        ]);

        $this->syncStaffRoles(
            $staff,
            $request->validated('roles', []),
            $request->user(),
        );

        return redirect()->route('tenant.staff.index')
            ->with('success', 'Staff member added successfully.');
    }

    /**
     * Show the form for editing a staff member.
     */
    public function edit(User $staff): RedirectResponse
    {
        abort_unless($staff->role === 'staff', 404);

        return redirect()->route('tenant.staff.index', [
            'edit' => $staff->id,
        ]);
    }

    /**
     * Update the specified staff member.
     */
    public function update(StaffRequest $request, User $staff): RedirectResponse
    {
        abort_unless($staff->role === 'staff', 404);

        Permission::ensureDefaultsExist();

        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        $staff->update($data);

        $this->syncStaffRoles(
            $staff,
            $request->validated('roles', []),
            $request->user(),
        );

        return redirect()->route('tenant.staff.index')
            ->with('success', 'Staff member updated successfully.');
    }

    /**
     * Remove the specified staff member.
     */
    public function destroy(User $staff): RedirectResponse
    {
        abort_unless($staff->role === 'staff', 404);

        $staff->delete();

        return redirect()->route('tenant.staff.index')
            ->with('success', 'Staff member removed successfully.');
    }

    /**
     * @return Collection<int, Role>
     */
    private function assignableRoles(?User $actor = null): Collection
    {
        if (! $this->hasRoleTables()) {
            return collect();
        }

        Role::ensureDefaultsExist();

        $roles = Role::query()
            ->whereNotIn('slug', ['owner', 'customer', 'staff'])
            ->with('permissions')
            ->orderBy('name')
            ->get();

        if ($actor === null || $actor->isOwner()) {
            return $roles;
        }

        return $roles
            ->filter(fn (Role $role): bool => $actor->canAssignRole($role))
            ->values();
    }

    /**
     * @param  list<string>  $requestedRoleSlugs
     */
    private function syncStaffRoles(
        User $staff,
        array $requestedRoleSlugs,
        ?User $actor,
    ): void {
        if (! $actor) {
            abort(403, 'Unauthorized.');
        }

        if (! $this->hasRoleTables()) {
            return;
        }

        if (! $actor->isOwner() && ! $actor->hasPermission('staff.assign_roles')) {
            return;
        }

        $roleSlugs = $this->allowedRoleSlugsForActor($actor, $requestedRoleSlugs)
            ->unique()
            ->prepend('staff')
            ->values()
            ->all();

        $staff->syncRolesBySlug($roleSlugs, $actor);
    }

    private function hasRoleTables(): bool
    {
        return Schema::hasTable('roles')
            && Schema::hasTable('role_user')
            && Schema::hasTable('permission_role');
    }

    /**
     * @param  list<string>  $requestedRoleSlugs
     * @return Collection<int, string>
     */
    private function allowedRoleSlugsForActor(
        User $actor,
        array $requestedRoleSlugs,
    ): Collection {
        $requested = collect($requestedRoleSlugs)
            ->filter(fn ($slug): bool => is_string($slug))
            ->reject(fn (string $slug): bool => in_array($slug, ['owner', 'customer', 'staff'], true))
            ->values();

        if ($actor->isOwner()) {
            return $requested;
        }

        $roles = Role::query()
            ->whereIn('slug', $requested->all())
            ->with('permissions')
            ->get()
            ->keyBy('slug');

        return $requested
            ->filter(fn (string $slug): bool => $roles->has($slug) && $actor->canAssignRole($roles->get($slug)))
            ->values();
    }
}
