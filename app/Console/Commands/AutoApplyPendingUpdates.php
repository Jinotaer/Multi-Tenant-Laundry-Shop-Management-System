<?php

namespace App\Console\Commands;

use App\Models\TenantUpdate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class AutoApplyPendingUpdates extends Command
{
    protected $signature = 'updates:auto-apply {--dry-run : List pending updates without applying them}';

    protected $description = 'Automatically apply pending updates for all tenants (requires AUTO_APPLY_UPDATES=true)';

    public function handle(): int
    {
        if (! config('updates.auto_apply', false)) {
            $this->info('Auto-apply is disabled. Set AUTO_APPLY_UPDATES=true in .env to enable.');
            return Command::SUCCESS;
        }

        $pending = TenantUpdate::where('status', 'update_available')
            ->with(['tenant', 'release'])
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending updates found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$pending->count()} pending update(s).");

        foreach ($pending as $update) {
            $tenantId  = $update->tenant_id;
            $releaseId = $update->app_release_id;
            $version   = $update->release?->version_tag ?? '?';

            if ($this->option('dry-run')) {
                $this->line("  [dry-run] Would apply {$version} → tenant {$tenantId}");
                continue;
            }

            $this->line("  Applying {$version} → tenant {$tenantId}...");

            try {
                $exitCode = Artisan::call('update:apply', [
                    'release_id' => $releaseId,
                    'tenant_id'  => $tenantId,
                ]);

                if ($exitCode === Command::SUCCESS) {
                    $this->info("  ✓ {$tenantId} updated to {$version}");
                } else {
                    $this->warn("  ✗ {$tenantId} update failed (exit {$exitCode}). Check storage/app/deployments/status.json");
                    Log::error('updates:auto-apply: update:apply returned non-zero exit.', compact('tenantId', 'version', 'exitCode'));
                }
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenantId}: {$e->getMessage()}");
                Log::error('updates:auto-apply: exception during update:apply.', [
                    'tenantId' => $tenantId,
                    'version'  => $version,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
