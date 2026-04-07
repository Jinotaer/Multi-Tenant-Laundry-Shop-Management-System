<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantMetricService;
use Illuminate\Console\Command;

class ResetMonthlyUsage extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenants:reset-usage 
                            {tenant? : Optional tenant ID to reset usage for a specific tenant}';

    /**
     * The console command description.
     */
    protected $description = 'Reset monthly bandwidth and API request counters for tenants';

    /**
     * Execute the console command.
     */
    public function handle(TenantMetricService $metricService): int
    {
        $tenantId = $this->argument('tenant');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);

            if (! $tenant) {
                $this->error("Tenant {$tenantId} not found.");

                return Command::FAILURE;
            }

            $metricService->resetMonthlyUsage($tenant);
            $this->info("Reset monthly usage for tenant: {$tenant->id}");

            return Command::SUCCESS;
        }

        // Reset for all tenants
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');

            return Command::SUCCESS;
        }

        $this->info("Resetting monthly usage for {$tenants->count()} tenant(s)...");

        $progressBar = $this->output->createProgressBar($tenants->count());
        $progressBar->start();

        foreach ($tenants as $tenant) {
            $metricService->resetMonthlyUsage($tenant);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Monthly usage reset completed for all tenants.');

        return Command::SUCCESS;
    }
}
