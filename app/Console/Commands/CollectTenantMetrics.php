<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantMetricService;
use Illuminate\Console\Command;

class CollectTenantMetrics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenants:collect-metrics 
                            {tenant? : Optional tenant ID to collect metrics for a specific tenant}';

    /**
     * The console command description.
     */
    protected $description = 'Collect and record usage metrics for all tenants';

    /**
     * Execute the console command.
     */
    public function handle(TenantMetricService $metricService): int
    {
        $tenantId = $this->argument('tenant');

        if ($tenantId) {
            return $this->collectForSingleTenant($tenantId, $metricService);
        }

        return $this->collectForAllTenants($metricService);
    }

    /**
     * Collect metrics for a single tenant.
     */
    protected function collectForSingleTenant(string $tenantId, TenantMetricService $metricService): int
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant {$tenantId} not found.");

            return Command::FAILURE;
        }

        $this->info("Collecting metrics for tenant: {$tenant->id}");

        try {
            $metric = $metricService->collectMetrics($tenant);

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Database Size', $metric->formatted_database_size],
                    ['Storage Size', $metric->formatted_storage],
                    ['API Requests', number_format($metric->api_requests_count)],
                    ['Bandwidth', $metric->formatted_bandwidth],
                    ['Active Users', $metric->active_users_count],
                    ['Orders', $metric->orders_count],
                    ['Customers', $metric->customers_count],
                ]
            );

            $this->info('Metrics collected successfully.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to collect metrics: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Collect metrics for all tenants.
     */
    protected function collectForAllTenants(TenantMetricService $metricService): int
    {
        $tenants = Tenant::where('is_enabled', true)->get();

        if ($tenants->isEmpty()) {
            $this->warn('No enabled tenants found.');

            return Command::SUCCESS;
        }

        $this->info("Collecting metrics for {$tenants->count()} tenant(s)...");

        $progressBar = $this->output->createProgressBar($tenants->count());
        $progressBar->start();

        $results = [];
        $errors = [];

        foreach ($tenants as $tenant) {
            try {
                $metric = $metricService->collectMetrics($tenant);
                $results[] = [
                    $tenant->id,
                    $metric->formatted_database_size,
                    $metric->formatted_storage,
                    number_format($metric->api_requests_count),
                    $metric->formatted_bandwidth,
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'tenant' => $tenant->id,
                    'error' => $e->getMessage(),
                ];
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if (! empty($results)) {
            $this->info('Metrics Summary:');
            $this->table(
                ['Tenant ID', 'DB Size', 'Storage', 'API Requests', 'Bandwidth'],
                $results
            );
        }

        if (! empty($errors)) {
            $this->newLine();
            $this->warn('Errors occurred:');
            foreach ($errors as $error) {
                $this->error("  - {$error['tenant']}: {$error['error']}");
            }
        }

        $this->info('Metric collection completed.');

        return empty($errors) ? Command::SUCCESS : Command::FAILURE;
    }
}
