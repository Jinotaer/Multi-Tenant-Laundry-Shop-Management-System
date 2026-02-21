<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        $totalTenants = Tenant::count();
        $recentTenants = Tenant::latest()->take(10)->get();

        return view('admin.dashboard', [
            'totalTenants' => $totalTenants,
            'recentTenants' => $recentTenants,
        ]);
    }
}
