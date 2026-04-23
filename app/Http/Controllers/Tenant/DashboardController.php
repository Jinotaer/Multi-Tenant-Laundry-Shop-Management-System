<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\LayoutSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the tenant dashboard based on user role.
     */
    public function index(Request $request, LayoutSettingsService $layoutSettingsService): View|RedirectResponse
    {
        $user = auth()->guard('web')->user() ?? auth()->guard('customer')->user();

        // Customer dashboard - check if user is Customer model instance
        if ($user instanceof Customer) {
            $customer = $user;
            $completedStatuses = array_values(array_unique([Order::terminalStatusForPlan(), 'delivered']));
            $activeOrders = collect();
            $orderHistory = collect();
            $totalOrders = 0;
            $totalSpent = 0;
            $loyalty = null;

            if ($customer) {
                if (tenant()->hasFeature('customer_loyalty')) {
                    $loyalty = $customer->loyalty()->firstOrCreate([], [
                        'points' => 0,
                        'stamps' => 0,
                        'tier' => 'bronze',
                        'lifetime_spent' => 0,
                    ]);
                }

                $activeOrders = Order::where('customer_id', $customer->id)
                    ->with('service')
                    ->whereNotIn('status', $completedStatuses)
                    ->latest()
                    ->get();

                $orderHistory = Order::where('customer_id', $customer->id)
                    ->with('service')
                    ->when($request->search, fn ($q) => $q->where('order_number', 'like', "%{$request->search}%"))
                    ->latest()
                    ->paginate(10)
                    ->withQueryString();

                $totalOrders = Order::where('customer_id', $customer->id)->count();
                $totalSpent = Order::where('customer_id', $customer->id)
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');
            }

            return view('tenant.portal.dashboard', compact('user', 'customer', 'activeOrders', 'orderHistory', 'totalOrders', 'totalSpent', 'loyalty'));
        }

        // Staff dashboard (owner, staff, manager, supervisor, etc.)
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

        // Last 7 days revenue for chart
        $weeklyRevenueRaw = Order::where('payment_status', 'paid')
            ->where('paid_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(paid_at) as day_date, SUM(total_amount) as revenue')
            ->groupBy('day_date')
            ->orderBy('day_date')
            ->get()
            ->keyBy('day_date');

        $weeklyRevenueDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $weeklyRevenueDays[] = [
                'label' => $date->format('D'),
                'amount' => (float) ($weeklyRevenueRaw[$date->format('Y-m-d')]->revenue ?? 0),
            ];
        }

        // Top services by order count
        $topServices = Order::selectRaw('service_id, COUNT(*) as order_count, SUM(total_amount) as total_revenue')
            ->with('service:id,name')
            ->whereNotNull('service_id')
            ->groupBy('service_id')
            ->orderByDesc('order_count')
            ->limit(4)
            ->get();

        // Orders by hour today (for peak hours bar chart)
        $ordersByHourRaw = Order::selectRaw('HOUR(created_at) as hr, COUNT(*) as cnt')
            ->whereDate('created_at', today())
            ->groupBy('hr')
            ->get()
            ->keyBy('hr');

        $peakHours = [];
        foreach ([8, 10, 12, 14, 16, 18] as $hr) {
            $peakHours[] = [
                'label' => $hr < 12 ? ($hr . 'AM') : (($hr === 12 ? '12' : ($hr - 12)) . 'PM'),
                'count' => (int) ($ordersByHourRaw[$hr]->cnt ?? 0),
            ];
        }

        return view('tenant.dashboard', [
            'user' => $user,
            'shopName' => tenant('data')['shop_name'] ?? tenant('id'),
            'dashboardWidgets' => $layoutSettingsService->dashboardWidgetsFor(tenant(), $user),
            'totalCustomers' => $totalCustomers,
            'totalOrders' => $totalOrders,
            'ordersByStatus' => $ordersByStatus,
            'recentOrders' => $recentOrders,
            'todayRevenue' => $todayRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'staffCount' => $staffCount,
            'weeklyRevenueDays' => $weeklyRevenueDays,
            'topServices' => $topServices,
            'peakHours' => $peakHours,
        ]);
    }
}
