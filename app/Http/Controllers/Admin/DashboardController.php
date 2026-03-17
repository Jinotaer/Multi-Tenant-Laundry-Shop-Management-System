<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantRegistration;
use App\Services\AdminLayoutSettingsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(AdminLayoutSettingsService $adminLayoutSettingsService): View
    {
        $admin = auth('admin')->user();
        $totalTenants = Tenant::count();
        $pendingRegistrations = TenantRegistration::where('status', 'pending')->count();
        $activeWorkspaces = Tenant::query()
            ->where(function ($query): void {
                $query->where('is_paid', true)
                    ->orWhere('trial_ends_at', '>', now());
            })
            ->count();
        $recentTenants = Tenant::latest()->take(10)->get();

        return view('admin.dashboard', [
            'dashboardWidgets' => $adminLayoutSettingsService->dashboardWidgetsFor($admin),
            'totalTenants' => $totalTenants,
            'pendingRegistrations' => $pendingRegistrations,
            'activeWorkspaces' => $activeWorkspaces,
            'recentTenants' => $recentTenants,
        ]);
    }
}
