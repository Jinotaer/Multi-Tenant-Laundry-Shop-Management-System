<?php

namespace App\Console\Commands;

use App\Models\AppRelease;
use App\Models\Tenant;
use App\Services\CodeDeploymentService;
use App\Services\TenantMigrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApplyUpdate extends Command
{
    protected $signature = 'update:apply {release_id} {tenant_id}';
    protected $description = 'Apply a release update — swap files, composer, migrate, rebuild cache.';

    public function handle(
        CodeDeploymentService $deployment,
        TenantMigrationService $migration,
    ): int {
        $releaseId = (int) $this->argument('release_id');
        $tenantId  = (string) $this->argument('tenant_id');

        $release = AppRelease::find($releaseId);
        $tenant  = Tenant::find($tenantId);

        $statusFile = storage_path('app/deployments/status.json');
        $history    = [];

        $write = function (string $stage, string $message, string $state = 'running', ?string $error = null) use ($statusFile, &$history): void {
            $history[] = ['at' => now()->toIso8601String(), 'stage' => $stage, 'message' => $message];
            $payload   = ['state' => $state, 'stage' => $stage, 'message' => $message, 'history' => $history];
            if ($error !== null) {
                $payload['error'] = $error;
            }
            @file_put_contents($statusFile, json_encode($payload));
        };

        if (! $release || ! $tenant) {
            $write('start', 'Release or tenant not found.', 'failed', "No record for release_id={$releaseId} tenant_id={$tenantId}.");
            return self::FAILURE;
        }

        try {
            $write('start', "Starting update to {$release->version_tag}…");

            // --- Phase 1: code file swap via CodeDeploymentService ---
            $result = $deployment->deployWithProgress(
                $release->version_tag,
                fn (string $stage, string $msg) => $write($stage, $msg),
            );

            if (! $result['success']) {
                throw new \RuntimeException($result['error'] ?? 'Code deployment failed.');
            }

            // --- Phase 2: tenant database migrations ---
            $write('migrate', 'Running tenant database migrations…');

            tenancy()->initialize($tenant);

            $currentUpdate  = $tenant->updates()->where('is_current', true)->with('release')->first();
            $currentVersion = $currentUpdate?->release?->version_tag ?? 'v0.0.0';

            $migResult = $migration->runMigrationsForVersion($tenant, $currentVersion, $release->version_tag);

            tenancy()->end();

            if (! $migResult['success']) {
                throw new \RuntimeException('Migrations failed: ' . ($migResult['error'] ?? 'unknown error'));
            }

            // Signal success — the status page JS will submit the finalize form
            // which writes the version record and clears maintenance mode.
            $write('finalize', "Files updated and migrations complete — finalising…", 'success');

            Log::info("update:apply completed.", [
                'tenant'  => $tenantId,
                'version' => $release->version_tag,
            ]);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            // Clear maintenance mode so the shop comes back online on old code.
            try {
                Cache::forget("tenant:update:maintenance:{$tenantId}");
            } catch (\Throwable) {}

            $write('rollback', $e->getMessage(), 'failed', $e->getMessage());

            Log::error("update:apply failed.", [
                'tenant'  => $tenantId,
                'version' => $release->version_tag ?? 'unknown',
                'error'   => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
