<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Services\CodeDeploymentService;
use App\Services\GitHubReleaseService;
use App\Services\TenantBackupService;
use App\Services\TenantMigrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateController extends Controller
{
    public function __construct(
        private TenantBackupService $backupService,
        private TenantMigrationService $migrationService,
        private CodeDeploymentService $deploymentService,
        private GitHubReleaseService $releaseService,
    ) {}

    /**
     * Display the version center for the tenant.
     */
    public function index()
    {
        $tenant = tenant();

        if (!$tenant) {
            abort(500, 'Tenant context not initialized');
        }
        
        $currentVersion = $tenant->currentVersion();
        $currentUpdate = $tenant->updates()->where('is_current', true)->with('release')->first();
        $currentVersionTag = $currentUpdate?->release?->version_tag ?? 'v0.0.0';

        // Sort by semantic version so tenants always see the true latest release first.
        $allReleases = $this->releaseService->sortReleasesDescending(AppRelease::all());
        
        // Filter newer versions for updates
        $availableUpdates = $allReleases->filter(function ($release) use ($currentVersionTag) {
            $releaseVersion = $this->releaseService->normalizeVersion($release->version_tag);
            $currentVer = $this->releaseService->normalizeVersion($currentVersionTag);

            return version_compare($releaseVersion, $currentVer, '>');
        });

        // Get update history from tenant_updates table
        $updateHistory = $tenant->updates()
            ->with('release')
            ->whereNotIn('status', ['update_available', 'deferred'])
            ->orderByDesc('created_at')
            ->get();

        // Check for pending migrations
        $hasPendingMigrations = false;
        try {
            $hasPendingMigrations = $this->migrationService->hasPendingMigrations($tenant);
        } catch (\Exception $e) {
            \Log::warning('Failed to check pending migrations', ['error' => $e->getMessage()]);
        }

        // Get available backups
        $backups = [];
        try {
            $backups = $this->backupService->listBackups($tenant->id);
        } catch (\Exception $e) {
            \Log::warning('Failed to list backups', ['error' => $e->getMessage()]);
        }

        return view('tenant.updates.index', compact(
            'currentVersion',
            'availableUpdates',
            'updateHistory',
            'hasPendingMigrations',
            'backups',
        ));
    }

    /**
     * Apply an update to the current tenant.
     */
    public function update(Request $request, AppRelease $release)
    {
        $tenant = tenant();
        $currentUpdate = $tenant->updates()->where('is_current', true)->with('release')->first();
        $currentVersion = $currentUpdate?->release?->version_tag ?? 'v0.0.0';
        $tenantBackupPath = null;
        $codeBackupPath = null;
        $migrationAttempted = false;
        $maintenanceModeEntered = false;
        $deployCode = $this->shouldDeployCode();

        try {
            // Step 1: Validate before changing state.
            Log::info("Starting update for tenant {$tenant->id} to {$release->version_tag}");

            if ($this->releaseService->isNewerVersion($release->version_tag, $currentVersion) && ! $deployCode) {
                $reason = (bool) config('updates.auto_deploy_code', false)
                    ? 'tenant-triggered shared code deployment is restricted'
                    : 'automatic code deployment is disabled';

                throw new \RuntimeException(
                    "Cannot apply {$release->version_tag} because {$reason}. " .
                    'Deploy the latest release code on this server first, then run the tenant update again.'
                );
            }

            $this->validatePreflight($deployCode);

            // Step 2: Create backup
            
            $backupResult = $this->backupService->createBackup($tenant, 'pre_update');
            
            if (!$backupResult['success']) {
                throw new \Exception('Backup failed: ' . $backupResult['error']);
            }

            $tenantBackupPath = $backupResult['backup_path'];

            // Step 3: Enter tenant-scoped maintenance mode.
            $maintenanceModeEntered = $this->enterTenantMaintenanceMode($tenant, $release->version_tag);

            // Step 4: Deploy code (if enabled)
            if ($deployCode) {
                $deployResult = $this->deploymentService->deployFromGitHub($release->version_tag);
                $codeBackupPath = $deployResult['backup_path'] ?? null;

                if (!$deployResult['success']) {
                    throw new \Exception('Code deployment failed: ' . $deployResult['error']);
                }
            }

            // Step 5: Run migrations
            $migrationAttempted = true;
            $migrationResult = $this->migrationService->runMigrationsForVersion(
                $tenant,
                $currentVersion,
                $release->version_tag
            );

            if (!$migrationResult['success']) {
                throw new \Exception('Migration failed: ' . $migrationResult['error']);
            }

            // Step 6: Run optional smoke test command.
            $smokeTestResult = $this->runOptionalSmokeTest();

            // Step 7: Update version record. The transaction must run on the
            // central connection because TenantUpdate forces that connection
            // (see TenantUpdate::getConnectionName). Without an explicit
            // connection, DB::transaction wraps the tenant DB while the writes
            // commit independently to central — if the second write fails, the
            // first commits anyway and the tenant ends up with no is_current
            // row, so currentVersion() falls back to its default and the UI
            // appears to "not update".
            $centralConnection = config('tenancy.database.central_connection');
            \Illuminate\Support\Facades\DB::connection($centralConnection)->transaction(function () use ($tenant, $release) {
                $tenant->updates()
                    ->where('tenant_id', $tenant->getKey())
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                $tenant->updates()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->getKey(),
                        'app_release_id' => $release->id,
                    ],
                    [
                        'status' => 'updated',
                        'is_current' => true,
                        'action_taken_at' => now(),
                    ]
                );
            });

            // Verify the write actually landed before reporting success — if
            // the relation comes back empty here, something silently failed
            // and we should surface it instead of showing a misleading
            // success message.
            $verifyCurrent = $tenant->updates()
                ->where('is_current', true)
                ->where('app_release_id', $release->id)
                ->exists();

            if (! $verifyCurrent) {
                throw new \RuntimeException(
                    "Version record write succeeded silently but no current row exists for {$release->version_tag}. " .
                    'Check the tenant_updates table on the central connection.'
                );
            }

            // Step 8: Exit maintenance mode only after successful completion.
            if ($maintenanceModeEntered) {
                $this->exitTenantMaintenanceMode($tenant);
                $maintenanceModeEntered = false;
            }

            Log::info("Tenant {$tenant->id} updated successfully to {$release->version_tag}");

            $successMessage =
                "Successfully updated to version {$release->version_tag}. " .
                "Backup created: {$backupResult['backup_name']}. " .
                "Migrations run: " . count($migrationResult['migrations'] ?? []);

            if (! $deployCode) {
                $reason = (bool) config('updates.auto_deploy_code', false)
                    ? 'to keep other tenant stores unaffected'
                    : 'because AUTO_DEPLOY_CODE is disabled';

                $successMessage .= ' Shared application code was not deployed on this server ' . $reason . '. ' .
                    'Deploy the latest release code on this device to access new feature pages and controllers.';
            }

            if ($smokeTestResult['executed']) {
                $successMessage .= ' Smoke test passed.';
            }

            return back()->with('success', $successMessage);

        } catch (\Exception $e) {
            if ($codeBackupPath || ($tenantBackupPath && $migrationAttempted)) {
                $this->restoreFailedVersionChange(
                    $tenant,
                    $tenantBackupPath,
                    $codeBackupPath,
                    restoreCodeFirst: true
                );
            }

            if ($maintenanceModeEntered) {
                Log::warning('Tenant update failed while maintenance mode is active; manual verification is required.', [
                    'tenant_id' => $tenant->id,
                    'target_version' => $release->version_tag,
                ]);
            }

            Log::error("Update failed for tenant {$tenant->id}", [
                'version' => $release->version_tag,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage =
                'Update failed: ' . $e->getMessage() . '. Please contact support if the issue persists.';

            if ($maintenanceModeEntered) {
                $errorMessage .= ' This store remains in maintenance mode until the update is verified.';
            }

            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Restore a failed update from backups.
     */
    private function restoreFailedVersionChange(
        $tenant,
        ?string $tenantBackupPath = null,
        ?string $codeBackupPath = null,
        bool $restoreCodeFirst = false
    ): void
    {
        try {
            Log::info("Restoring failed version change for tenant {$tenant->id}", [
                'restore_code_first' => $restoreCodeFirst,
                'has_tenant_backup' => $tenantBackupPath !== null,
                'has_code_backup' => $codeBackupPath !== null,
            ]);

            $steps = $restoreCodeFirst
                ? ['code', 'tenant']
                : ['tenant', 'code'];

            foreach ($steps as $step) {
                if ($step === 'tenant' && $tenantBackupPath) {
                    $result = $this->backupService->restoreBackup($tenant, $tenantBackupPath);

                    if (! $result['success']) {
                        throw new \Exception('Tenant restore failed: ' . $result['error']);
                    }
                }

                if ($step === 'code' && $codeBackupPath) {
                    $result = $this->deploymentService->rollbackCode($codeBackupPath);

                    if (! $result['success']) {
                        throw new \Exception('Code restore failed: ' . $result['error']);
                    }
                }
            }

            Log::info("Version recovery completed for tenant {$tenant->id}");
        } catch (\Exception $e) {
            Log::critical("Version recovery failed for tenant {$tenant->id}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create manual backup.
     */
    public function createBackup(Request $request)
    {
        $tenant = tenant();
        
        try {
            $result = $this->backupService->createBackup($tenant, 'manual');
            
            if ($result['success']) {
                return back()->with('success', 
                    "Backup created successfully: {$result['backup_name']}. " .
                    "Size: " . round($result['size'] / 1024 / 1024, 2) . " MB"
                );
            }
            
            return back()->with('error', 'Backup failed: ' . $result['error']);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Restore from backup.
     */
    public function restoreBackup(Request $request)
    {
        $request->validate([
            'backup_path' => 'required|string'
        ]);
        
        $tenant = tenant();
        
        try {
            $result = $this->backupService->restoreBackup($tenant, $request->backup_path);
            
            if ($result['success']) {
                return back()->with('success', 'Backup restored successfully.');
            }
            
            return back()->with('error', 'Restore failed: ' . $result['error']);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }

    /**
     * Trigger an on-demand GitHub release sync. Tenants click "Check for
     * Updates" to refresh the available-releases list without waiting for
     * the every-10-minute scheduled sync. The actual fetching is delegated
     * to GitHubReleaseService::syncReleases(true).
     */
    public function checkForUpdates(Request $request)
    {
        try {
            $synced = $this->releaseService->syncReleases(true);
        } catch (\Throwable $e) {
            \Log::error('Manual GitHub release sync failed.', [
                'tenant_id' => tenant()?->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Could not check for updates: ' . $e->getMessage());
        }

        if ($synced) {
            return back()->with('success', 'Checked GitHub for new releases. Refreshing the list now.');
        }

        return back()->with('error', 'Could not reach GitHub right now. We will retry automatically in the background.');
    }

    /**
     * Run pending migrations.
     */
    public function runMigrations(Request $request)
    {
        $tenant = tenant();
        $currentUpdate = $tenant->updates()->where('is_current', true)->with('release')->first();
        $currentVersion = $currentUpdate?->release?->version_tag ?? 'v0.0.0';
        
        try {
            $result = $this->migrationService->runMigrationsForVersion(
                $tenant,
                $currentVersion,
                $currentVersion
            );
            
            if ($result['success']) {
                return back()->with('success', 
                    'Migrations completed. ' . count($result['migrations']) . ' migrations run.'
                );
            }
            
            return back()->with('error', 'Migrations failed: ' . $result['error']);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Migrations failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate deployment prerequisites before changing tenant state.
     */
    private function validatePreflight(bool $deployCode): void
    {
        if (! $deployCode) {
            return;
        }

        $preflight = $this->deploymentService->canDeploy();

        if ($preflight['can_deploy'] ?? false) {
            return;
        }

        $failedChecks = collect($preflight['checks'] ?? [])
            ->filter(fn (array $check) => ! ($check['passed'] ?? false))
            ->map(fn (array $check, string $name) => $name . ': ' . ($check['message'] ?? 'failed'))
            ->values()
            ->implode('; ');

        throw new \RuntimeException(
            'Preflight validation failed' . ($failedChecks !== '' ? ': ' . $failedChecks : '.')
        );
    }

    /**
     * Run an optional smoke test command configured for updates.
     */
    private function runOptionalSmokeTest(): array
    {
        $enabled = (bool) config('updates.smoke_test.enabled', false);
        $command = trim((string) config('updates.smoke_test.command', ''));

        if (! $enabled || $command === '') {
            return ['executed' => false];
        }

        $output = [];
        $exitCode = 0;

        exec($command . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Smoke test failed: ' . trim(implode(PHP_EOL, $output)));
        }

        Log::info('Smoke test command completed successfully.', [
            'command' => $command,
        ]);

        return ['executed' => true];
    }

    /**
     * Enable tenant-scoped maintenance mode while update tasks execute.
     */
    private function enterTenantMaintenanceMode($tenant, string $targetVersion): bool
    {
        if (! (bool) config('updates.tenant_maintenance.enabled', true)) {
            return false;
        }

        $ttlMinutes = max((int) config('updates.tenant_maintenance.ttl_minutes', 60), 5);

        try {
            $this->maintenanceCache()->put(
                $this->tenantMaintenanceCacheKey($tenant->id),
                [
                    'tenant_id' => $tenant->id,
                    'target_version' => $targetVersion,
                    'started_at' => now()->toIso8601String(),
                ],
                now()->addMinutes($ttlMinutes)
            );
        } catch (\Throwable $e) {
            Log::warning('Unable to enable tenant maintenance mode for update; continuing without maintenance lock.', [
                'tenant_id' => $tenant->id,
                'target_version' => $targetVersion,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Disable tenant-scoped maintenance mode after a successful update.
     */
    private function exitTenantMaintenanceMode($tenant): void
    {
        try {
            $this->maintenanceCache()->forget($this->tenantMaintenanceCacheKey($tenant->id));
        } catch (\Throwable $e) {
            Log::warning('Unable to clear tenant maintenance mode after update.', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build cache key for tenant update maintenance state.
     */
    private function tenantMaintenanceCacheKey($tenantId): string
    {
        return 'tenant:update:maintenance:' . $tenantId;
    }

    /**
     * Resolve cache repository used for tenant update maintenance flags.
     */
    private function maintenanceCache()
    {
        $store = (string) config('updates.tenant_maintenance.cache_store', config('cache.default'));

        return Cache::store($store);
    }

    /**
     * Determine if code should be deployed alongside version changes.
     */
    private function shouldDeployCode(): bool
    {
        $autoDeployEnabled = (bool) config('updates.auto_deploy_code', false);

        if (! $autoDeployEnabled) {
            return false;
        }

        // Tenant-triggered updates should remain tenant-scoped and must not replace shared app code.
        if (function_exists('tenancy') && tenancy()->initialized
            && ! (bool) config('updates.allow_tenant_code_deploy', false)) {
            Log::warning('Skipping shared code deployment during tenant update to prevent cross-tenant impact.', [
                'tenant_id' => tenant()?->id,
                'updates.auto_deploy_code' => $autoDeployEnabled,
                'updates.allow_tenant_code_deploy' => (bool) config('updates.allow_tenant_code_deploy', false),
            ]);

            return false;
        }

        return true;
    }

}
