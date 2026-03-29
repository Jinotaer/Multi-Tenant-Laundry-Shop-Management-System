<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    /**
     * Display the advanced analytics dashboard.
     */
    public function index(Request $request): View
    {
        $period = $request->get('period', '30d');
        $days = match ($period) {
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();

        $revenueByDay = Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as report_date, SUM(total_amount) as total_revenue')
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get()
            ->pluck('total_revenue', 'report_date');

        $ordersByDay = Order::query()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as report_date, COUNT(*) as total_orders')
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get()
            ->pluck('total_orders', 'report_date');

        $timeline = collect(range(0, $days - 1))
            ->map(function (int $offset) use ($startDate, $revenueByDay, $ordersByDay): array {
                $date = $startDate->copy()->addDays($offset)->toDateString();

                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('M d'),
                    'revenue' => (float) ($revenueByDay[$date] ?? 0),
                    'orders' => (int) ($ordersByDay[$date] ?? 0),
                ];
            });

        $statusBreakdown = Order::query()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $topCustomers = Customer::query()
            ->withCount(['orders' => fn ($query) => $query->where('created_at', '>=', $startDate)])
            ->withSum(['orders' => fn ($query) => $query
                ->where('payment_status', 'paid')
                ->where('created_at', '>=', $startDate)], 'total_amount')
            ->orderByDesc('orders_sum_total_amount')
            ->limit(5)
            ->get();

        $topServices = Service::query()
            ->withCount(['orders' => fn ($query) => $query->where('created_at', '>=', $startDate)])
            ->orderByDesc('orders_count')
            ->limit(5)
            ->get();

        $lowStockItems = tenant()->hasFeature('inventory_management')
            ? InventoryItem::query()
                ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
                ->orderBy('quantity_on_hand')
                ->limit(5)
                ->get()
            : collect();

        return view('tenant.analytics.index', [
            'period' => $period,
            'timeline' => $timeline,
            'statusBreakdown' => $statusBreakdown,
            'topCustomers' => $topCustomers,
            'topServices' => $topServices,
            'lowStockItems' => $lowStockItems,
            'totalRevenue' => (float) $timeline->sum('revenue'),
            'totalOrders' => (int) $timeline->sum('orders'),
            'averageOrderValue' => $timeline->sum('orders') > 0
                ? (float) $timeline->sum('revenue') / max((int) $timeline->sum('orders'), 1)
                : 0,
        ]);
    }
}
