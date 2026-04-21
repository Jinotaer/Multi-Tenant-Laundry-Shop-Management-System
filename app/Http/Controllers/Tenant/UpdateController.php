<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Services\CodeDeploymentService;
use App\Services\GitHubReleaseService;
use App\Services\TenantBackupService;
use App\Services\TenantMigrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class UpdateController extends Controller
{
    public function __construct(
        private TenantBackupService $backupService,
        private TenantMigrationService $migrationService,
        private CodeDeploymentService $deploymentService
    ) {}

    /**
     * Display the version center for the tenant.
     */
    public function index()
    {
        $tenant = tenant();
        $currentVersion = $tenant->currentVersion();
        $currentUpdate = $tenant->updates()->where('is_current', true)->with('release')->first();
        $currentVersionTag = $currentUpdate?->release?->version_tag ?? 'v0.0.0';

        // Get all releases
        $allReleases = AppRelease::orderByDesc('published_at')->get();
        
        // Filter newer versions for updates
        $availableUpdates = $allReleases->filter(function ($release) use ($currentVersionTag) {
            $releaseVersion = ltrim($release->version_tag, 'v');
            $currentVer = ltrim($currentVersionTag, 'v');
            return version_compare($releaseVersion, $currentVer, '>');
        });

        // Get update history from tenant_updates table
        $updateHistory = $tenant->updates()
            ->with('release')
            ->whereNotIn('status', ['update_available', 'deferred'])
            ->orderByDesc('created_at')
            ->get();

        // Check for pending migrations
        $hasPendingMigrations = $this->migrationService->hasPendingMigrations($tenant);

        // Get available backups
        $backups = $this->backupService->listBackups($tenant->id);

        return view('tenant.updates.index', compact(
            'currentVersion',
            'availableUpdates',
            'updateHistory',
            'hasPendingMigrations',
            'backups'
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

        try {
            // Step 1: Create backup
            Log::info("Starting update for tenant {$tenant->id} to {$release->version_tag}");
            
            $backupResult = $this->backupService->createBackup($tenant, 'pre_update');
            
            if (!$backupResult['success']) {
                throw new \Exception('Backup failed: ' . $backupResult['error']);
            }

            // Step 2: Deploy code (if enabled)
            if (config('app.auto_deploy_code', false)) {
                $deployResult = $this->deploymentService->deployFromGitHub($release->version_tag);
                
                if (!$deployResult['success']) {
                    throw new \Exception('Code deployment failed: ' . $deployResult['error']);
                }
            }

            // Step 3: Run migrations
            $migrationResult = $this->migrationService->runMigrationsForVersion(
                $tenant,
                $currentVersion,
                $release->version_tag
            );

            if (!$migrationResult['success']) {
                // Rollback on migration failure
                $this->rollbackUpdate($tenant, $backupResult['backup_path']);
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
    public function rollback(Request $request, AppRelease $release, GitHubReleaseService $service)
    {
        $tenant = tenant();
        $currentUpdate = $tenant->updates()->where('is_current', true)->with('release')->first();
        $currentVersion = $currentUpdate?->release?->version_tag ?? 'v0.0.0';

        // Check if rollback is safe
        $safetyCheck = $service->canRollbackTo($release, $tenant);

        if (!$safetyCheck['can_rollback']) {
            return back()->with('error', 'Rollback not allowed: '.implode(' ', $safetyCheck['errors']));
        }

        try {
            // Create backup before rollback
            $backupResult = $this->backupService->createBackup($tenant, 'pre_rollback');
            
            if (!$backupResult['success']) {
                throw new \Exception('Backup failed: ' . $backupResult['error']);
            }

            // Rollback migrations
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
    private function rollbackUpdate($tenant, string $backupPath): void
    {
        try {
            Log::info("Rolling back failed update for tenant {$tenant->id}");
            
            $this->backupService->restoreBackup($tenant, $backupPath);
            
            Log::info("Rollback completed for tenant {$tenant->id}");
        } catch (\Exception $e) {
            Log::critical("Rollback failed for tenant {$tenant->id}", [
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
}
