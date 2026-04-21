<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Models\AppReleaseForceRun;
use App\Models\Tenant;
use App\Models\TenantUpdate;
use App\Services\CodeDeploymentService;
use App\Services\GitHubReleaseService;
use App\Services\TenantMigrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReleaseController extends Controller
{
    public function __construct(
        private CodeDeploymentService $deploymentService,
        private TenantMigrationService $migrationService,
    ) {}

    /**
     * Display a listing of all releases.
     */
    public function index()
    {
        $releases = AppRelease::orderByDesc('published_at')->paginate(15);
        $totalTenants = Tenant::count();

        // Count how many tenants are currently using each release
        $releases->getCollection()->transform(function ($release) {
            $release->active_tenants_count = TenantUpdate::where('app_release_id', $release->id)
                ->where('is_current', true)
                ->count();

            return $release;
        });

        return view('admin.releases.index', compact('releases', 'totalTenants'));
    }

    /**
     * Sync releases manually from GitHub.
     */
    public function sync(GitHubReleaseService $service)
    {
        $success = $service->syncReleases(true); // Force sync

        if ($success) {
            return back()->with('success', 'GitHub releases synced successfully! Tenants have been notified of available updates.');
        }

        return back()->with('error', 'Failed to sync releases. Check the GitHub service configuration or rate limits.');
    }

    /**
     * Show details for a specific release and its adoption rate.
     */
    public function show(AppRelease $release)
    {
        $tenantUpdates = TenantUpdate::with('tenant')
            ->where('app_release_id', $release->id)
            ->where('is_current', true)
            ->paginate(20);

        $forceRuns = AppReleaseForceRun::query()
            ->with('admin')
            ->where('app_release_id', $release->id)
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.releases.show', compact('release', 'tenantUpdates', 'forceRuns'));
    }

    /**
     * Force this release onto all tenants.
     */
    public function forceUpdateAll(Request $request, AppRelease $release)
    {
        $tenants = Tenant::all();
        $forceRun = AppReleaseForceRun::create([
            'app_release_id' => $release->id,
            'admin_id' => auth('admin')->id(),
            'status' => 'running',
            'deployment_success' => false,
            'total_tenants' => $tenants->count(),
            'successful_tenants' => 0,
            'failed_tenants' => 0,
            'failed_tenant_ids' => [],
            'started_at' => now(),
        ]);

        $deployResult = $this->deploymentService->deployFromGitHub($release->version_tag);

        if (! ($deployResult['success'] ?? false)) {
            $error = $deployResult['error'] ?? 'Unknown deployment error.';

            $forceRun->update([
                'status' => 'failed',
                'deployment_success' => false,
                'deployment_error' => $error,
                'finished_at' => now(),
            ]);

            return back()->with('error', 'Force update failed during code deployment: ' . $error);
        }

        $successCount = 0;
        $failedTenantIds = [];

        foreach ($tenants as $tenant) {
            try {
                $currentVersion = $tenant->currentVersion();
                $targetVersion = $release->version_tag;

                if ($currentVersion !== $targetVersion) {
                    $migrationResult = $this->migrationService->runMigrationsForVersion(
                        $tenant,
                        $currentVersion,
                        $targetVersion
                    );

                    if (! ($migrationResult['success'] ?? false)) {
                        throw new \RuntimeException($migrationResult['error'] ?? 'Migration failed.');
                    }
                }

                // Deactivate old current version
                $tenant->updates()->where('is_current', true)->update(['is_current' => false]);

                // Activate new version
                $tenant->updates()->updateOrCreate(
                    ['app_release_id' => $release->id],
                    [
                        'status' => 'updated',
                        'is_current' => true,
                        'action_taken_at' => now(),
                    ]
                );

                $successCount++;
            } catch (\Throwable $e) {
                $failedTenantIds[] = $tenant->id;

                Log::error('Force update tenant failed', [
                    'tenant_id' => $tenant->id,
                    'target_version' => $release->version_tag,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Mark the release as required globally
        $release->update(['is_required' => true]);

        $forceRun->update([
            'status' => empty($failedTenantIds) ? 'completed' : 'partial',
            'deployment_success' => true,
            'deployment_error' => null,
            'successful_tenants' => $successCount,
            'failed_tenants' => count($failedTenantIds),
            'failed_tenant_ids' => array_values($failedTenantIds),
            'finished_at' => now(),
        ]);

        if (! empty($failedTenantIds)) {
            return back()->with('error',
                'Code deployed, but tenant update failed for: ' . implode(', ', $failedTenantIds) .
                ". Updated {$successCount}/{$tenants->count()} tenant(s)."
            );
        }

        return back()->with('success',
            "Forced {$release->version_tag} to all tenants ({$successCount}/{$tenants->count()}) with code deployment."
        );
    }
}
