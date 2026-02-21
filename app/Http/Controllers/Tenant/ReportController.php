<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display the reports dashboard.
     */
    public function index(Request $request): View
    {
        $period = $request->get('period', 'month');
        $startDate = match ($period) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        // Revenue & order stats for the period
        $periodOrders = Order::where('created_at', '>=', $startDate);
        $totalRevenue = (clone $periodOrders)->where('payment_status', 'paid')->sum('total_amount');
        $totalOrders = (clone $periodOrders)->count();
        $paidOrders = (clone $periodOrders)->where('payment_status', 'paid')->count();
        $unpaidOrders = (clone $periodOrders)->where('payment_status', 'unpaid')->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / max($paidOrders, 1) : 0;

        // Orders by status
        $ordersByStatus = Order::where('created_at', '>=', $startDate)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Daily revenue for chart (last 30 days)
        $dailyRevenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as revenue'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->pluck('revenue', 'date')
            ->toArray();

        // Popular services
        $popularServices = Service::withCount(['orders' => fn ($q) => $q->where('created_at', '>=', $startDate)])
            ->orderByDesc('orders_count')
            ->limit(5)
            ->get();

        // Recent orders
        $recentOrders = Order::with(['customer', 'service'])
            ->latest()
            ->limit(10)
            ->get();

        // Summary counts
        $totalCustomers = Customer::count();

        return view('tenant.reports.index', compact(
            'period',
            'totalRevenue',
            'totalOrders',
            'paidOrders',
            'unpaidOrders',
            'averageOrderValue',
            'ordersByStatus',
            'dailyRevenue',
            'popularServices',
            'recentOrders',
            'totalCustomers',
        ));
    }
}
