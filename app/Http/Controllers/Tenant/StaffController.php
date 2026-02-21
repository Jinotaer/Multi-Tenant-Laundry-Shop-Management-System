<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StaffRequest;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffController extends Controller
{
    /**
     * Display a listing of all staff members.
     */
    public function index(Request $request): View
    {
        $staff = User::query()
            ->where('role', 'staff')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $planLimitService = new PlanLimitService(tenant());
        $canAddStaff = $planLimitService->canAddStaff(User::where('role', 'staff')->count());

        return view('tenant.staff.index', compact('staff', 'canAddStaff'));
    }

    /**
     * Show the form for creating a new staff member.
     */
    public function create(): View
    {
        $planLimitService = new PlanLimitService(tenant());
        $canAddStaff = $planLimitService->canAddStaff(User::where('role', 'staff')->count());

        if (! $canAddStaff) {
            return view('tenant.staff.limit-reached');
        }

        return view('tenant.staff.create');
    }

    /**
     * Store a newly created staff member.
     */
    public function store(StaffRequest $request): RedirectResponse
    {
        $planLimitService = new PlanLimitService(tenant());

        if (! $planLimitService->canAddStaff(User::where('role', 'staff')->count())) {
            return redirect()->route('tenant.staff.index')
                ->with('error', 'Staff limit reached for your current plan. Please upgrade.');
        }

        User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'role' => 'staff',
        ]);

        return redirect()->route('tenant.staff.index')
            ->with('success', 'Staff member added successfully.');
    }

    /**
     * Show the form for editing a staff member.
     */
    public function edit(User $staff): View
    {
        abort_unless($staff->role === 'staff', 404);

        return view('tenant.staff.edit', compact('staff'));
    }

    /**
     * Update the specified staff member.
     */
    public function update(StaffRequest $request, User $staff): RedirectResponse
    {
        abort_unless($staff->role === 'staff', 404);

        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        $staff->update($data);

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
}
