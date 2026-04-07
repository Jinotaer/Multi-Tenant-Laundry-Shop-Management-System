<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TenantMetricService
{
    /**
     * Collect and record all metrics for a tenant.
     */
    public function collectMetrics(Tenant $tenant): TenantMetric
    {
        $databaseSize = $this->calculateDatabaseSize($tenant);
        $storageSize = $this->calculateStorageSize($tenant);
        $tenantStats = $this->getTenantStats($tenant);

        // Create metric record
        $metric = TenantMetric::create([
            'tenant_id' => $tenant->id,
            'database_size_mb' => $databaseSize,
            'storage_size_mb' => $storageSize,
            'api_requests_count' => $tenant->current_api_requests ?? 0,
            'bandwidth_mb' => $tenant->current_bandwidth_mb ?? 0,
            'active_users_count' => $tenantStats['active_users'] ?? 0,
            'orders_count' => $tenantStats['orders'] ?? 0,
            'customers_count' => $tenantStats['customers'] ?? 0,
            'recorded_at' => now(),
        ]);

        // Update tenant's current storage value
        $tenant->update([
            'current_storage_mb' => $databaseSize + $storageSize,
        ]);

        return $metric;
    }

    /**
     * Calculate the database size for a tenant.
     */
    public function calculateDatabaseSize(Tenant $tenant): float
    {
        try {
            $dbName = 'tenant'.$tenant->id;

            $result = DB::select('
                SELECT COALESCE(SUM(data_length + index_length) / 1024 / 1024, 0) AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = ?
            ', [$dbName]);

            return (float) ($result[0]->size_mb ?? 0);
        } catch (\Exception $e) {
            Log::warning("Failed to calculate database size for tenant {$tenant->id}: ".$e->getMessage());

            return 0;
        }
    }

    /**
     * Calculate the file storage size for a tenant.
     */
    public function calculateStorageSize(Tenant $tenant): float
    {
        try {
            $paths = [
                storage_path("app/public/tenant{$tenant->id}"),
                storage_path("app/tenant{$tenant->id}"),
                storage_path("tenant{$tenant->id}"),
            ];

            $totalSize = 0;

            foreach ($paths as $path) {
                if (File::isDirectory($path)) {
                    $totalSize += $this->getDirectorySize($path);
                }
            }

            // Convert bytes to MB
            return $totalSize / 1024 / 1024;
        } catch (\Exception $e) {
            Log::warning("Failed to calculate storage size for tenant {$tenant->id}: ".$e->getMessage());

            return 0;
        }
    }

    /**
     * Get directory size in bytes recursively.
     */
    protected function getDirectorySize(string $path): int
    {
        $size = 0;

        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * Get tenant statistics (users, orders, customers).
     */
    public function getTenantStats(Tenant $tenant): array
    {
        try {
            $dbName = 'tenant'.$tenant->id;

            // Check if tables exist and get counts
            $stats = [
                'active_users' => 0,
                'orders' => 0,
                'customers' => 0,
            ];

            // Switch to tenant database temporarily
            config(['database.connections.tenant_stats.database' => $dbName]);
            $connection = DB::connection('mysql');

            // Get users count
            $usersResult = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.TABLES 
                WHERE table_schema = ? AND table_name = 'users'
            ", [$dbName]);

            if ($usersResult[0]->count > 0) {
                $count = DB::select("SELECT COUNT(*) as count FROM `{$dbName}`.`users`");
                $stats['active_users'] = (int) ($count[0]->count ?? 0);
            }

            // Get orders count
            $ordersResult = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.TABLES 
                WHERE table_schema = ? AND table_name = 'orders'
            ", [$dbName]);

            if ($ordersResult[0]->count > 0) {
                $count = DB::select("SELECT COUNT(*) as count FROM `{$dbName}`.`orders`");
                $stats['orders'] = (int) ($count[0]->count ?? 0);
            }

            // Get customers count
            $customersResult = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.TABLES 
                WHERE table_schema = ? AND table_name = 'customers'
            ", [$dbName]);

            if ($customersResult[0]->count > 0) {
                $count = DB::select("SELECT COUNT(*) as count FROM `{$dbName}`.`customers`");
                $stats['customers'] = (int) ($count[0]->count ?? 0);
            }

            return $stats;
        } catch (\Exception $e) {
            Log::warning("Failed to get tenant stats for {$tenant->id}: ".$e->getMessage());

            return [
                'active_users' => 0,
                'orders' => 0,
                'customers' => 0,
            ];
        }
    }

    /**
     * Increment API request count for a tenant.
     */
    public function incrementApiRequests(Tenant $tenant, int $count = 1): void
    {
        $tenant->increment('current_api_requests', $count);
    }

    /**
     * Add bandwidth usage for a tenant.
     */
    public function addBandwidth(Tenant $tenant, float $bytes): void
    {
        $mb = $bytes / 1024 / 1024;
        $tenant->increment('current_bandwidth_mb', $mb);
    }

    /**
     * Reset monthly usage counters for a tenant.
     */
    public function resetMonthlyUsage(Tenant $tenant): void
    {
        $tenant->update([
            'current_bandwidth_mb' => 0,
            'current_api_requests' => 0,
            'usage_reset_at' => now(),
        ]);
    }

    /**
     * Get usage summary for all tenants.
     */
    public function getAllTenantsUsageSummary(): array
    {
        return Tenant::with('subscriptionPlan')
            ->get()
            ->map(function (Tenant $tenant) {
                return [
                    'tenant' => $tenant,
                    'storage_mb' => $tenant->current_storage_mb,
                    'storage_percentage' => $tenant->getStorageUsagePercentage(),
                    'bandwidth_mb' => $tenant->current_bandwidth_mb,
                    'bandwidth_percentage' => $tenant->getBandwidthUsagePercentage(),
                    'api_requests' => $tenant->current_api_requests,
                    'storage_exceeded' => $tenant->isStorageLimitExceeded(),
                    'bandwidth_exceeded' => $tenant->isBandwidthLimitExceeded(),
                ];
            })
            ->toArray();
    }

    /**
     * Get tenants that are approaching or exceeding limits.
     */
    public function getTenantsNearLimits(int $thresholdPercentage = 80): array
    {
        return Tenant::with('subscriptionPlan')
            ->get()
            ->filter(function (Tenant $tenant) use ($thresholdPercentage) {
                $storagePercentage = $tenant->getStorageUsagePercentage();
                $bandwidthPercentage = $tenant->getBandwidthUsagePercentage();

                return ($storagePercentage !== null && $storagePercentage >= $thresholdPercentage)
                    || ($bandwidthPercentage !== null && $bandwidthPercentage >= $thresholdPercentage);
            })
            ->values()
            ->toArray();
    }
}
