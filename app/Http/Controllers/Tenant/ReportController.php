<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display the reports dashboard.
     */
    public function index(Request $request): View
    {
        return view('tenant.reports.index', $this->buildReportData($request));
    }

    /**
     * Export report data in an Excel-compatible CSV format.
     */
    public function exportExcel(Request $request): Response
    {
        $report = $this->buildReportData($request);
        $statusLabels = Order::statusLabelsForPlan();
        $rows = [
            ['Metric', 'Value'],
            ['Period', $report['periodLabel']],
            ['Total Revenue', number_format($report['totalRevenue'], 2, '.', '')],
            ['Total Expenses', number_format($report['totalExpenses'], 2, '.', '')],
            ['Estimated Profit', number_format($report['estimatedProfit'], 2, '.', '')],
            ['Total Orders', $report['totalOrders']],
            ['Paid Orders', $report['paidOrders']],
            ['Unpaid Orders', $report['unpaidOrders']],
            ['Average Order Value', number_format($report['averageOrderValue'], 2, '.', '')],
            ['Total Customers', $report['totalCustomers']],
            [],
            ['Orders By Status', 'Count'],
        ];

        foreach ($report['ordersByStatus'] as $status => $count) {
            $rows[] = [$statusLabels[$status] ?? ucfirst($status), $count];
        }

        if ($report['popularServices']->isNotEmpty()) {
            $rows[] = [];
            $rows[] = ['Popular Services', 'Orders'];

            foreach ($report['popularServices'] as $service) {
                $rows[] = [$service->name, $service->orders_count];
            }
        }

        if ($report['recentOrders']->isNotEmpty()) {
            $rows[] = [];
            $rows[] = ['Recent Orders', ''];
            $rows[] = ['Order Number', 'Customer', 'Service', 'Status', 'Total', 'Payment Status'];

            foreach ($report['recentOrders'] as $order) {
                $rows[] = [
                    $order->order_number,
                    $order->customer?->name ?? 'Walk-in',
                    $order->service?->name ?? 'N/A',
                    $statusLabels[$order->status] ?? ucfirst($order->status),
                    number_format((float) $order->total_amount, 2, '.', ''),
                    $order->payment_status,
                ];
            }
        }

        $csv = collect($rows)
            ->map(fn (array $row): string => collect($row)
                ->map(fn (mixed $value): string => '"'.str_replace('"', '""', (string) $value).'"')
                ->implode(','))
            ->implode("\n");

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laundry-report-'.$report['period'].'-'.now()->format('YmdHis').'.csv"',
        ]);
    }

    /**
     * Render a print-ready report view suitable for browser PDF export.
     */
    public function exportPdf(Request $request): View
    {
        return view('tenant.reports.print', $this->buildReportData($request));
    }

    /**
     * Build report metrics for the requested period.
     *
     * @return array<string, mixed>
     */
    private function buildReportData(Request $request): array
    {
        $period = $request->get('period', 'month');
        $startDate = match ($period) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $periodOrders = Order::query()->where('created_at', '>=', $startDate);
        $totalRevenue = (clone $periodOrders)->where('payment_status', 'paid')->sum('total_amount');
        $totalOrders = (clone $periodOrders)->count();
        $paidOrders = (clone $periodOrders)->where('payment_status', 'paid')->count();
        $unpaidOrders = (clone $periodOrders)->where('payment_status', 'unpaid')->count();
        $averageOrderValue = $paidOrders > 0 ? $totalRevenue / $paidOrders : 0;
        $totalExpenses = tenant()->hasFeature('expense_tracking')
            ? Expense::query()->where('expense_date', '>=', $startDate->toDateString())->sum('amount')
            : 0;
        $estimatedProfit = $totalRevenue - $totalExpenses;

        $ordersByStatus = Order::query()
            ->where('created_at', '>=', $startDate)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $dailyRevenue = Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as revenue'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->pluck('revenue', 'date')
            ->toArray();

        $popularServices = Service::query()
            ->withCount(['orders' => fn ($query) => $query->where('created_at', '>=', $startDate)])
            ->orderByDesc('orders_count')
            ->limit(5)
            ->get();

        $recentOrders = Order::query()
            ->with(['customer', 'service'])
            ->latest()
            ->limit(10)
            ->get();

        return [
            'period' => $period,
            'periodLabel' => match ($period) {
                'week' => 'This Week',
                'year' => 'This Year',
                default => 'This Month',
            },
            'generatedAt' => now(),
            'totalRevenue' => (float) $totalRevenue,
            'totalExpenses' => (float) $totalExpenses,
            'estimatedProfit' => (float) $estimatedProfit,
            'totalOrders' => $totalOrders,
            'paidOrders' => $paidOrders,
            'unpaidOrders' => $unpaidOrders,
            'averageOrderValue' => (float) $averageOrderValue,
            'ordersByStatus' => $ordersByStatus,
            'dailyRevenue' => $dailyRevenue,
            'popularServices' => $popularServices,
            'recentOrders' => $recentOrders,
            'totalCustomers' => Customer::count(),
        ];
    }
}
