<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Services\CodeDeploymentService;
use App\Services\GitHubReleaseService;
use App\Services\TenantBackupService;
use App\Services\TenantMigrationService;
use Illuminate\Http\Request;
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
        $canApplyUpdates = $this->currentUserCanApplyUpdates(auth()->user());
        
        if (!$tenant) {
            abort(500, 'Tenant context not initialized');
        }
        
        $currentVersion = $tenant->currentVersion();
        $currentUpdate = $tenant->updates()->where('is_current', true)->with('release')->first();
        $currentVersionTag = $currentUpdate?->release?->version_tag ?? 'v0.0.0';

        // Get all releases
        $allReleases = AppRelease::orderByDesc('published_at')->get();
        
        // Filter newer versions for updates
        $availableUpdates = $allReleases->filter(function ($release) use ($currentVersionTag) {
            $releaseVersion = $this->releaseService->normalizeVersion($release->version_tag);
            $currentVer = $this->releaseService->normalizeVersion($currentVersionTag);

            return version_compare($releaseVersion, $currentVer, '>');
        });

        // Filter older versions that can be rolled back to
        $rollbackCandidates = $allReleases->filter(function ($release) use ($currentVersionTag) {
            $releaseVersion = $this->releaseService->normalizeVersion($release->version_tag);
            $currentVer = $this->releaseService->normalizeVersion($currentVersionTag);

            return version_compare($releaseVersion, $currentVer, '<');
        })->map(function ($release) use ($tenant) {
            $rollbackCheck = $this->releaseService->canRollbackTo($release, $tenant);

            $release->can_rollback = $rollbackCheck['can_rollback'];
            $release->rollback_errors = $rollbackCheck['errors'];
            $release->rollback_warnings = $rollbackCheck['warnings'];

            return $release;
        });

        // Get update history from tenant_updates table
        $updateHistory = $tenant->updates()
            ->with('release')
            ->whereNotIn('status', ['update_available', 'deferred'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($history) use ($tenant, $canApplyUpdates) {
                $history->can_rollback = false;
                $history->rollback_errors = [];
                $history->rollback_warnings = [];

                if (! $canApplyUpdates || $history->is_current || ! $history->release) {
                    return $history;
                }

                $rollbackCheck = $this->releaseService->canRollbackTo($history->release, $tenant);

                $history->can_rollback = $rollbackCheck['can_rollback'];
                $history->rollback_errors = $rollbackCheck['errors'];
                $history->rollback_warnings = $rollbackCheck['warnings'];

                return $history;
            });

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
            'rollbackCandidates',
            'updateHistory',
            'hasPendingMigrations',
            'backups',
            'canApplyUpdates',
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

        try {
            // Step 1: Create backup
            Log::info("Starting update for tenant {$tenant->id} to {$release->version_tag}");
            
            $backupResult = $this->backupService->createBackup($tenant, 'pre_update');
            
            if (!$backupResult['success']) {
                throw new \Exception('Backup failed: ' . $backupResult['error']);
            }

            $tenantBackupPath = $backupResult['backup_path'];

            // Step 2: Deploy code (if enabled)
            if ($this->shouldDeployCode()) {
                $deployResult = $this->deploymentService->deployFromGitHub($release->version_tag);
                $codeBackupPath = $deployResult['backup_path'] ?? null;

                if (!$deployResult['success']) {
                    throw new \Exception('Code deployment failed: ' . $deployResult['error']);
                }
            }

            // Step 3: Run migrations
            $migrationAttempted = true;
            $migrationResult = $this->migrationService->runMigrationsForVersion(
                $tenant,
                $currentVersion,
                $release->version_tag
            );

            if (!$migrationResult['success']) {
                throw new \Exception('Migration failed: ' . $migrationResult['error']);
            }

            // Step 4: Update version record
            $tenant->updates()->where('is_current', true)->update(['is_current' => false]);

            $tenant->updates()->updateOrCreate(
                ['app_release_id' => $release->id],
                [
                    'status' => 'updated',
                    'is_current' => true,
                    'action_taken_at' => now(),
                ]
            );

            Log::info("Tenant {$tenant->id} updated successfully to {$release->version_tag}");

            return back()->with('success', 
                "Successfully updated to version {$release->version_tag}. " .
                "Backup created: {$backupResult['backup_name']}. " .
                "Migrations run: " . count($migrationResult['migrations'] ?? [])
            );

        } catch (\Exception $e) {
            if ($codeBackupPath || ($tenantBackupPath && $migrationAttempted)) {
                $this->restoreFailedVersionChange(
                    $tenant,
                    $tenantBackupPath,
                    $codeBackupPath,
                    restoreCodeFirst: true
                );
            }

            Log::error("Update failed for tenant {$tenant->id}", [
                'version' => $release->version_tag,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 
                'Update failed: ' . $e->getMessage() . '. Please contact support if the issue persists.'
            );
        }
    }

    /**
     * Rollback to a previous release.
     */
    public function rollback(Request $request, AppRelease $release)
    {
        $tenant = tenant();
        $currentUpdate = $tenant->updates()->where('is_current', true)->with('release')->first();
        $currentVersion = $currentUpdate?->release?->version_tag ?? 'v0.0.0';
        $tenantBackupPath = null;
        $codeBackupPath = null;
        $rollbackAttempted = false;

        // Check if rollback is safe
        $safetyCheck = $this->releaseService->canRollbackTo($release, $tenant);

        if (!$safetyCheck['can_rollback']) {
            return back()->with('error', 'Rollback not allowed: '.implode(' ', $safetyCheck['errors']));
        }

        try {
            // Create backup before rollback
            $backupResult = $this->backupService->createBackup($tenant, 'pre_rollback');
            
            if (!$backupResult['success']) {
                throw new \Exception('Backup failed: ' . $backupResult['error']);
            }

            $tenantBackupPath = $backupResult['backup_path'];

            // Roll back code if automatic deployments are enabled
            if ($this->shouldDeployCode()) {
                $deployResult = $this->deploymentService->deployFromGitHub($release->version_tag);
                $codeBackupPath = $deployResult['backup_path'] ?? null;

                if (!$deployResult['success']) {
                    throw new \Exception('Code rollback failed: ' . $deployResult['error']);
                }
            }

            // Rollback migrations
            $rollbackAttempted = true;
            $migrationResult = $this->migrationService->rollbackMigrationsForVersion(
                $tenant,
                $currentVersion,
                $release->version_tag
            );

            if (!$migrationResult['success']) {
                throw new \Exception('Migration rollback failed: ' . $migrationResult['error']);
            }

            // Update version record
            $tenant->updates()->where('is_current', true)->update(['is_current' => false]);

            $tenant->updates()->updateOrCreate(
                ['app_release_id' => $release->id],
                [
                    'status' => 'rolled_back',
                    'is_current' => true,
                    'action_taken_at' => now(),
                ]
            );

            Log::info("Tenant {$tenant->id} rolled back to {$release->version_tag}");

            $warningMessage = !empty($safetyCheck['warnings'])
                ? ' Warning: '.implode(' ', $safetyCheck['warnings'])
                : '';

            return back()->with('success', 
                "Rolled back to version {$release->version_tag}. " .
                "Backup created: {$backupResult['backup_name']}." .
                $warningMessage
            );

        } catch (\Exception $e) {
            if ($codeBackupPath || ($tenantBackupPath && $rollbackAttempted)) {
                $this->restoreFailedVersionChange(
                    $tenant,
                    $tenantBackupPath,
                    $codeBackupPath,
                    restoreCodeFirst: false
                );
            }

            Log::error("Rollback failed for tenant {$tenant->id}", [
                'version' => $release->version_tag,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 
                'Rollback failed: ' . $e->getMessage() . '. Please contact support.'
            );
        }
    }

    /**
     * Rollback update on failure.
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
     * Determine if code should be deployed alongside version changes.
     */
    private function shouldDeployCode(): bool
    {
        return (bool) config('updates.auto_deploy_code', false);
    }

    /**
     * Determine if the current actor can apply updates and rollbacks.
     */
    private function currentUserCanApplyUpdates($user): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isOwner') && $user->isOwner()) {
            return true;
        }

        return method_exists($user, 'hasPermission')
            && $user->hasPermission('updates.apply');
    }
}
