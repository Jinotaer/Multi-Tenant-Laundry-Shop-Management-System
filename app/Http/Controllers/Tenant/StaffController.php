<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StaffRequest;
use App\Models\Permission;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
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

        $staff = User::query()
            ->where('role', 'staff')
            ->with('permissions')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $planLimitService = new PlanLimitService(tenant());
        $canAddStaff = $planLimitService->canAddStaff(User::where('role', 'staff')->count());
        $canCreateStaff = $actor !== null && ($actor->isOwner() || $actor->hasPermission('staff.create'));

        return view('tenant.staff.index', compact('staff', 'canAddStaff', 'canCreateStaff'));
    }

    /**
     * Show the form for creating a new staff member.
     */
    public function create(): View
    {
        Permission::ensureDefaultsExist();
        $actor = request()->user();
        $canManagePermissions = $actor !== null
            && ($actor->isOwner() || $actor->hasPermission('permissions.manage'));

        $planLimitService = new PlanLimitService(tenant());
        $canAddStaff = $planLimitService->canAddStaff(User::where('role', 'staff')->count());

        if (! $canAddStaff) {
            return view('tenant.staff.limit-reached');
        }

        $permissionsByModule = Permission::query()
            ->orderBy('module')
            ->orderBy('label')
            ->get()
            ->groupBy('module');

        return view('tenant.staff.create', [
            'permissionsByModule' => $permissionsByModule,
            'canManagePermissions' => $canManagePermissions,
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

        $this->syncStaffPermissions(
            $staff,
            $request->validated('permissions', []),
            $request->user(),
        );

        return redirect()->route('tenant.staff.index')
            ->with('success', 'Staff member added successfully.');
    }

    /**
     * Show the form for editing a staff member.
     */
    public function edit(User $staff): View
    {
        abort_unless($staff->role === 'staff', 404);

        Permission::ensureDefaultsExist();
        $actor = request()->user();
        $canManagePermissions = $actor !== null
            && ($actor->isOwner() || $actor->hasPermission('permissions.manage'));

        $permissionsByModule = Permission::query()
            ->orderBy('module')
            ->orderBy('label')
            ->get()
            ->groupBy('module');

        $assignedPermissionKeys = $staff->permissions()
            ->pluck('key')
            ->all();

        return view('tenant.staff.edit', compact(
            'staff',
            'permissionsByModule',
            'assignedPermissionKeys',
            'canManagePermissions',
        ));
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

        $this->syncStaffPermissions(
            $staff,
            $request->validated('permissions', []),
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
     * @param  list<string>  $requestedPermissionKeys
     */
    private function syncStaffPermissions(
        User $staff,
        array $requestedPermissionKeys,
        ?User $actor,
    ): void {
        if (! $actor) {
            abort(403, 'Unauthorized.');
        }

        if (! $actor->isOwner() && ! $actor->hasPermission('permissions.manage')) {
            return;
        }

        $allowedPermissionKeys = $this->allowedPermissionKeysForActor(
            $actor,
            $requestedPermissionKeys,
        );

        $permissionIds = Permission::query()
            ->whereIn('key', $allowedPermissionKeys)
            ->pluck('id')
            ->all();

        $syncPayload = [];

        foreach ($permissionIds as $permissionId) {
            $syncPayload[$permissionId] = ['granted_by' => $actor->id];
        }

        $staff->permissions()->sync($syncPayload);
    }

    /**
     * @param  list<string>  $requestedPermissionKeys
     * @return Collection<int, string>
     */
    private function allowedPermissionKeysForActor(
        User $actor,
        array $requestedPermissionKeys,
    ): Collection {
        $requested = collect($requestedPermissionKeys)
            ->filter(fn ($key): bool => is_string($key))
            ->values();

        if ($actor->isOwner()) {
            return $requested;
        }

        return $requested
            ->filter(fn (string $key): bool => $actor->canGrantPermission($key))
            ->values();
    }
}
