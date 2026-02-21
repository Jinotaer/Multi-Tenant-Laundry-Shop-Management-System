<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the tenant dashboard based on user role.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Redirect customer role to portal
        if ($user->isCustomer()) {
            return redirect()->route('tenant.portal.index');
        }

        $totalCustomers = Customer::count();
        $totalOrders = Order::count();
        $ordersByStatus = Order::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        $recentOrders = Order::with(['customer', 'service'])->latest()->limit(5)->get();

        // Owner-specific stats
        $todayRevenue = Order::where('payment_status', 'paid')
            ->whereDate('paid_at', today())
            ->sum('total_amount');
        $monthlyRevenue = Order::where('payment_status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total_amount');
        $staffCount = User::where('role', 'staff')->count();

        return view('tenant.dashboard', [
            'user' => $user,
            'shopName' => tenant('data')['shop_name'] ?? tenant('id'),
            'totalCustomers' => $totalCustomers,
            'totalOrders' => $totalOrders,
            'ordersByStatus' => $ordersByStatus,
            'recentOrders' => $recentOrders,
            'todayRevenue' => $todayRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'staffCount' => $staffCount,
        ]);
    }
}
