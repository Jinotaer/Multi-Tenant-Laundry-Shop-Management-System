<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantMetric;
use App\Services\TenantMetricService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TenantMonitoringController extends Controller
{
    public function __construct(
        protected TenantMetricService $metricService
    ) {}

    /**
     * Display the monitoring dashboard overview.
     */
    public function index(Request $request): View
    {
        $query = Tenant::with(['subscriptionPlan', 'registration'])
            ->whereHas('registration', fn ($q) => $q->where('status', 'approved'));

        // Sort options
        $sortField = $request->get('sort', 'current_storage_mb');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSorts = ['current_storage_mb', 'current_bandwidth_mb', 'current_api_requests', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $tenants = $query->paginate(20)->withQueryString();

        // Calculate summary statistics
        $totalTenants = Tenant::whereHas('registration', fn ($q) => $q->where('status', 'approved'))->count();
        $totalStorageMb = Tenant::sum('current_storage_mb');
        $totalBandwidthMb = Tenant::sum('current_bandwidth_mb');

        return view('admin.monitoring.index', [
            'tenants' => $tenants,
            'totalTenants' => $totalTenants,
            'totalStorageMb' => $totalStorageMb,
            'totalBandwidthMb' => $totalBandwidthMb,
            'currentSort' => $sortField,
            'currentDirection' => $sortDirection,
        ]);
    }

    /**
     * Display detailed metrics for a specific tenant.
     */
    public function show(Tenant $tenant, Request $request): View
    {
        $tenant->load(['subscriptionPlan', 'registration']);

        // Get historical metrics
        $days = $request->get('days', 30);
        $metrics = TenantMetric::where('tenant_id', $tenant->id)
            ->where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at', 'asc')
            ->get();

        // Prepare chart data
        $chartData = [
            'labels' => $metrics->pluck('recorded_at')->map(fn ($d) => $d->format('M d'))->toArray(),
            'storage' => $metrics->pluck('storage_size_mb')->toArray(),
            'database' => $metrics->pluck('database_size_mb')->toArray(),
            'bandwidth' => $metrics->pluck('bandwidth_mb')->toArray(),
            'apiRequests' => $metrics->pluck('api_requests_count')->toArray(),
        ];

        // Get latest metric
        $latestMetric = $tenant->latestMetric();

        return view('admin.monitoring.show', [
            'tenant' => $tenant,
            'metrics' => $metrics,
            'chartData' => $chartData,
            'latestMetric' => $latestMetric,
            'days' => $days,
        ]);
    }

    /**
     * Manually refresh metrics for a tenant.
     */
    public function refresh(Tenant $tenant)
    {
        $metric = $this->metricService->collectMetrics($tenant);

        return redirect()->back()->with('success', 'Metrics refreshed successfully.');
    }

    /**
     * Update resource limits for a tenant.
     */
    public function updateLimits(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'storage_limit_mb' => ['nullable', 'numeric', 'min:0'],
            'bandwidth_limit_mb' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tenant->update([
            'storage_limit_mb' => $validated['storage_limit_mb'] ?: null,
            'bandwidth_limit_mb' => $validated['bandwidth_limit_mb'] ?: null,
        ]);

        $shopName = $tenant->data['shop_name'] ?? $tenant->id;

        return redirect()->back()->with('success', "Resource limits updated for '{$shopName}'.");
    }

    /**
     * Export metrics to CSV.
     */
    public function export(Request $request): Response
    {
        $tenants = Tenant::with(['subscriptionPlan', 'registration'])
            ->whereHas('registration', fn ($q) => $q->where('status', 'approved'))
            ->orderBy('current_storage_mb', 'desc')
            ->get();

        $csv = "Tenant ID,Shop Name,Plan,Storage (MB),Storage Limit (MB),Storage %,Bandwidth (MB),Bandwidth Limit (MB),Bandwidth %,API Requests,Status\n";

        foreach ($tenants as $tenant) {
            $shopName = $tenant->data['shop_name'] ?? $tenant->id;
            $planName = $tenant->subscriptionPlan?->name ?? 'None';
            $storageLimit = $tenant->getEffectiveStorageLimit() ?? 'Unlimited';
            $bandwidthLimit = $tenant->getEffectiveBandwidthLimit() ?? 'Unlimited';
            $storagePercent = $tenant->getStorageUsagePercentage() ?? 'N/A';
            $bandwidthPercent = $tenant->getBandwidthUsagePercentage() ?? 'N/A';

            $status = 'Healthy';
            if ($tenant->isStorageLimitExceeded() || $tenant->isBandwidthLimitExceeded()) {
                $status = 'Exceeded';
            } elseif (($storagePercent !== 'N/A' && $storagePercent >= 80) ||
                      ($bandwidthPercent !== 'N/A' && $bandwidthPercent >= 80)) {
                $status = 'Warning';
            }

            $csv .= sprintf(
                "%s,\"%s\",%s,%.2f,%s,%s,%.2f,%s,%s,%d,%s\n",
                $tenant->id,
                str_replace('"', '""', $shopName),
                $planName,
                $tenant->current_storage_mb,
                $storageLimit,
                is_numeric($storagePercent) ? number_format($storagePercent, 1).'%' : $storagePercent,
                $tenant->current_bandwidth_mb,
                $bandwidthLimit,
                is_numeric($bandwidthPercent) ? number_format($bandwidthPercent, 1).'%' : $bandwidthPercent,
                $tenant->current_api_requests,
                $status
            );
        }

        $filename = 'tenant-metrics-'.now()->format('Y-m-d').'.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
