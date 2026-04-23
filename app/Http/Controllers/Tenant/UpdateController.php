<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Services\CodeDeploymentService;
use App\Services\GitHubReleaseService;
use App\Services\TenantBackupService;
use App\Services\TenantMigrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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

        // Check for an in-progress (staged but not yet applied) update
        $applyingUpdate = $tenant->updates()
            ->where('status', 'applying')
            ->with('release')
            ->latest('action_taken_at')
            ->first();

        // Check for pending migrations
        $hasPendingMigrations = false;
        try {
            $hasPendingMigrations = $this->migrationService->hasPendingMigrations($tenant);
        } catch (\Exception $e) {
            Log::warning('Failed to check pending migrations', ['error' => $e->getMessage()]);
        }

        // Get available backups
        $backups = [];
        try {
            $backups = $this->backupService->listBackups($tenant->id);
        } catch (\Exception $e) {
            Log::warning('Failed to list backups', ['error' => $e->getMessage()]);
        }

        return view('tenant.updates.index', compact(
            'currentVersion',
            'availableUpdates',
            'updateHistory',
            'hasPendingMigrations',
            'backups',
            'applyingUpdate',
        ));
    }

    /**
     * Apply an update to the current tenant.
     *
     * When code deployment is enabled (Option B) the heavy lifting is handed
     * off to the external updater: we stage the release (download + extract)
     * within this request, launch apply.bat detached, and immediately redirect
     * to the status page. This avoids HTTP timeouts during composer/npm/migrate.
     *
     * When code deployment is disabled the existing synchronous path runs:
     * backup → maintenance mode → tenant migrations → version record.
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

            // Step 2: Create tenant DB backup
            $backupResult = $this->backupService->createBackup($tenant, 'pre_update');

            if (!$backupResult['success']) {
                throw new \Exception('Backup failed: ' . $backupResult['error']);
            }

            $tenantBackupPath = $backupResult['backup_path'];

            // Step 3: Enter tenant-scoped maintenance mode.
            $maintenanceModeEntered = $this->enterTenantMaintenanceMode($tenant, $release->version_tag);

            // Step 4: Launch artisan updater detached — returns immediately.
            if ($deployCode) {
                return $this->launchArtisanUpdate($request, $tenant, $release, $tenantBackupPath, $maintenanceModeEntered);
            }

            // --- No-deploy path: run tenant migrations and record the version ---

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

            // Step 7: Update version record on the central connection.
            $this->commitVersionRecord($tenant, $release);

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
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = 'Update failed: ' . $e->getMessage() . '. Please contact support if the issue persists.';

            if ($maintenanceModeEntered) {
                $errorMessage .= ' This store remains in maintenance mode until the update is verified.';
            }

            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Launch `php artisan update:apply` detached and redirect to the status page.
     *
     * Uses PHP_BINARY so there are no PATH issues. On Windows, Apache does not
     * hold open handles to .php files between requests, so files can be safely
     * overwritten without stopping Apache first.
     */
    private function launchArtisanUpdate(
        Request $request,
        $tenant,
        AppRelease $release,
        string $tenantBackupPath,
        bool $maintenanceModeEntered
    ): \Illuminate\Http\RedirectResponse {
        try {
            // Clear any stale status file from a previous failed run.
            $statusFile = storage_path('app/deployments/status.json');
            if (File::exists($statusFile)) {
                File::delete($statusFile);
            }
            File::ensureDirectoryExists(storage_path('app/deployments'));

            // Mark as 'applying' so the index page shows an in-progress banner
            // if the user navigates away while the update runs.
            $centralConnection = config('tenancy.database.central_connection');
            DB::connection($centralConnection)->transaction(function () use ($tenant, $release) {
                $tenant->updates()
                    ->where('tenant_id', $tenant->getKey())
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                $tenant->updates()->updateOrCreate(
                    ['tenant_id' => $tenant->getKey(), 'app_release_id' => $release->id],
                    ['status' => 'applying', 'is_current' => false, 'action_taken_at' => now()]
                );
            });

            // Build the artisan command using the exact PHP binary that is
            // running this request — no PATH resolution needed.
            $php    = PHP_BINARY;
            $artisan = base_path('artisan');
            $args   = implode(' ', [
                escapeshellarg($release->id),
                escapeshellarg((string) $tenant->getKey()),
            ]);

            if (PHP_OS_FAMILY === 'Windows') {
                // start /B spawns detached; pclose returns in ~100 ms.
                $command = "start \"LaundryUpdater\" /B \"{$php}\" \"{$artisan}\" update:apply {$args}";
            } else {
                $command = "\"{$php}\" \"{$artisan}\" update:apply {$args} > /dev/null 2>&1 &";
            }

            $handle = popen($command, 'r');
            if ($handle === false) {
                throw new \RuntimeException('Failed to launch update process.');
            }
            pclose($handle);

            // Persist backup path so finalizeUpdate() can restore on failure.
            $request->session()->put('update_finalize', [
                'release_id'         => $release->id,
                'version_tag'        => $release->version_tag,
                'tenant_backup_path' => $tenantBackupPath,
            ]);

            Log::info("Artisan updater launched for tenant {$tenant->id} → {$release->version_tag}");

            return redirect()->route('tenant.updates.status', ['release' => $release->id]);

        } catch (\Exception $e) {
            if ($maintenanceModeEntered) {
                $this->exitTenantMaintenanceMode($tenant);
            }

            if ($tenantBackupPath) {
                try {
                    $this->backupService->restoreBackup($tenant, $tenantBackupPath);
                } catch (\Throwable) {}
            }

            // Restore is_current on the most recent 'updated' row so
            // currentVersion() reflects the actual running version.
            try {
                $centralConnection = config('tenancy.database.central_connection');
                $previous = $tenant->updates()
                    ->where('status', 'updated')
                    ->latest('action_taken_at')
                    ->first();
                if ($previous) {
                    DB::connection($centralConnection)
                        ->table('tenant_updates')
                        ->where('id', $previous->id)
                        ->update(['is_current' => true]);
                }
            } catch (\Throwable) {}

            Log::error("Failed to launch artisan update for tenant {$tenant->id}", [
                'version' => $release->version_tag,
                'error'   => $e->getMessage(),
            ]);

            return back()->with('error', 'Update failed to start: ' . $e->getMessage());
        }
    }

    /**
     * Show the live-progress status page while the external updater runs.
     */
    public function updateStatus(Request $request, AppRelease $release)
    {
        $tenant = tenant();
        $statusFile = storage_path('app/deployments/status.json');

        $status = null;
        if (File::exists($statusFile)) {
            $decoded = json_decode(File::get($statusFile), true);
            if (is_array($decoded)) {
                $status = $decoded;
            }
        }

        $finalizeData = $request->session()->get('update_finalize');

        return view('tenant.updates.status', compact('release', 'status', 'finalizeData'));
    }

    /**
     * JSON polling endpoint read by the status page JS.
     * Returns the current contents of status.json, or a 'pending' state when
     * the updater hasn't written anything yet.
     */
    public function pollStatus(Request $request, AppRelease $release): JsonResponse
    {
        $statusFile = storage_path('app/deployments/status.json');

        if (! File::exists($statusFile)) {
            return response()->json([
                'state' => 'pending',
                'stage' => 'queued',
                'message' => 'Update is queued — waiting for the updater to start.',
            ]);
        }

        $decoded = json_decode(File::get($statusFile), true);

        if (! is_array($decoded)) {
            return response()->json([
                'state' => 'pending',
                'stage' => 'queued',
                'message' => 'Reading updater status…',
            ]);
        }

        return response()->json($decoded);
    }

    /**
     * Finalise a completed external update: write the version record and clear
     * maintenance mode. Called automatically by the status page JS after the
     * updater reports success.
     */
    public function finalizeUpdate(Request $request, AppRelease $release)
    {
        $tenant = tenant();
        $statusFile = storage_path('app/deployments/status.json');

        // Verify the updater actually finished successfully before touching DB.
        if (File::exists($statusFile)) {
            $status = json_decode(File::get($statusFile), true);
            if (is_array($status) && ($status['state'] ?? '') === 'failed') {
                $errorMsg = $status['message'] ?? 'Unknown error';
                Log::error("Finalize requested but updater reported failure for tenant {$tenant->id}", [
                    'version' => $release->version_tag,
                    'updater_message' => $errorMsg,
                ]);
                return redirect()->route('tenant.updates.status', ['release' => $release->id])
                    ->with('error', "Update failed: {$errorMsg}");
            }
        }

        $finalizeData = $request->session()->get('update_finalize', []);

        try {
            // Update version record on the central connection.
            $this->commitVersionRecord($tenant, $release);

            // Clear maintenance mode.
            $this->exitTenantMaintenanceMode($tenant);

            // Tidy up status file and session.
            if (File::exists($statusFile)) {
                File::delete($statusFile);
            }
            $request->session()->forget('update_finalize');

            Log::info("Tenant {$tenant->id} finalized update to {$release->version_tag}");

            return redirect()->route('tenant.updates.index')
                ->with('success', "Successfully updated to {$release->version_tag}!");

        } catch (\Exception $e) {
            // If finalizing the version record fails, try to restore the tenant
            // DB backup so the shop's data is consistent with the old code.
            $tenantBackupPath = $finalizeData['tenant_backup_path'] ?? null;
            if ($tenantBackupPath) {
                try {
                    $this->backupService->restoreBackup($tenant, $tenantBackupPath);
                } catch (\Throwable) {}
            }

            Log::error("Failed to finalize update for tenant {$tenant->id}", [
                'version' => $release->version_tag,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('tenant.updates.index')
                ->with('error', 'Update completed but version record failed: ' . $e->getMessage());
        }
    }

    /**
     * Restore from backup.
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
            'backup_path' => 'required|string',
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
     * Trigger an on-demand GitHub release sync.
     */
    public function checkForUpdates(Request $request)
    {
        try {
            $synced = $this->releaseService->syncReleases(true);
        } catch (\Throwable $e) {
            Log::error('Manual GitHub release sync failed.', [
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

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Write the is_current version record on the central DB connection.
     *
     * The transaction must use an explicit central connection because
     * TenantUpdate forces that connection (see TenantUpdate::getConnectionName).
     * Without an explicit connection, DB::transaction wraps the tenant DB while
     * the writes commit independently to central — if the second write fails,
     * the first commits anyway and the tenant ends up with no is_current row,
     * so currentVersion() falls back to its sentinel value and the UI appears
     * to "not update".
     */
    private function commitVersionRecord($tenant, AppRelease $release): void
    {
        $centralConnection = config('tenancy.database.central_connection');

        DB::connection($centralConnection)->transaction(function () use ($tenant, $release) {
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
    }

    /**
     * Restore a failed update from backups.
     */
    private function restoreFailedVersionChange(
        $tenant,
        ?string $tenantBackupPath = null,
        ?string $codeBackupPath = null,
        bool $restoreCodeFirst = false
    ): void {
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
                'error' => $e->getMessage(),
            ]);
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

        Log::info('Smoke test command completed successfully.', ['command' => $command]);

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
