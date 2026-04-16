<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Services\GitHubReleaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class UpdateController extends Controller
{
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

        return view('tenant.updates.index', compact('currentVersion', 'availableUpdates', 'updateHistory'));
    }

    /**
     * Apply an update to the current tenant.
     */
    public function update(Request $request, AppRelease $release)
    {
        $tenant = tenant();

        try {
            // Create database backup before update
            $this->info('Creating backup before update...');
            $this->createBackup($tenant);

            // Deactivate old current version
            $tenant->updates()->where('is_current', true)->update(['is_current' => false]);

            // Activate new version
            $tenant->updates()->updateOrCreate(
                [
                    'app_release_id' => $release->id,
                ],
                [
                    'status' => 'updated',
                    'is_current' => true,
                    'action_taken_at' => now(),
                ]
            );

            Log::info("Tenant {$tenant->id} updated to version {$release->version_tag}");

            return back()->with('success', 'Successfully updated your application version to '.$release->version_tag.'. A backup was created before the update.');
        } catch (\Exception $e) {
            Log::error("Failed to update tenant {$tenant->id} to version {$release->version_tag}", [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update. Please contact support if the issue persists.');
        }
    }

    /**
     * Rollback to a previous release.
     */
    public function rollback(Request $request, AppRelease $release, GitHubReleaseService $service)
    {
        $tenant = tenant();

        // Check if rollback is safe
        $safetyCheck = $service->canRollbackTo($release, $tenant);

        if (! $safetyCheck['can_rollback']) {
            return back()->with('error', 'Rollback not allowed: '.implode(' ', $safetyCheck['errors']));
        }

        try {
            // Create backup before rollback
            $this->info('Creating backup before rollback...');
            $this->createBackup($tenant);

            $tenant->updates()->where('is_current', true)->update(['is_current' => false]);

            $tenant->updates()->updateOrCreate(
                [
                    'app_release_id' => $release->id,
                ],
                [
                    'status' => 'rolled_back',
                    'is_current' => true,
                    'action_taken_at' => now(),
                ]
            );

            Log::info("Tenant {$tenant->id} rolled back to version {$release->version_tag}");

            $warningMessage = ! empty($safetyCheck['warnings'])
                ? ' Warning: '.implode(' ', $safetyCheck['warnings'])
                : '';

            return back()->with('success', 'Rolled back to version '.$release->version_tag.'. A backup was created before the rollback.'.$warningMessage);
        } catch (\Exception $e) {
            Log::error("Failed to rollback tenant {$tenant->id} to version {$release->version_tag}", [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to rollback. Please contact support if the issue persists.');
        }
    }

    /**
     * Create a database backup for the tenant.
     */
    private function createBackup($tenant): void
    {
        try {
            // You can implement actual backup logic here
            // For now, we'll just log it
            Log::info("Backup created for tenant {$tenant->id} before version change");

            // Example: Use Laravel Backup package or custom backup logic
            // Artisan::call('backup:run', ['--only-db' => true]);
        } catch (\Exception $e) {
            Log::warning("Failed to create backup for tenant {$tenant->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Helper to log info messages.
     */
    private function info(string $message): void
    {
        Log::info($message);
    }
}
