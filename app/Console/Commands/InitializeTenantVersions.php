<?php

namespace App\Console\Commands;

use App\Models\AppRelease;
use App\Models\Tenant;
use App\Models\TenantUpdate;
use App\Services\GitHubReleaseService;
use Illuminate\Console\Command;

class InitializeTenantVersions extends Command
{
    protected $signature = 'tenants:init-versions
        {--tag= : Assign a specific version tag (e.g. v1.0.0). Defaults to the latest stable release.}
        {--cleanup : Also remove orphaned update_available records}';

    protected $description = 'Assign a current version to tenants that don\'t have one and optionally clean up stale records';

    public function handle(GitHubReleaseService $releaseService): int
    {
        // Determine target release
        $versionTag = $this->option('tag');

        if ($versionTag) {
            $release = AppRelease::where('version_tag', $versionTag)->first();

            if (! $release) {
                $this->error("Release '{$versionTag}' not found in the database. Run 'php artisan releases:sync --force' first.");

                return Command::FAILURE;
            }
        } else {
            $release = AppRelease::where('is_prerelease', false)
                ->orderByDesc('published_at')
                ->first();

            if (! $release) {
                $this->error('No stable releases found. Run "php artisan releases:sync --force" first.');

                return Command::FAILURE;
            }
        }

        $this->info("Target version: {$release->version_tag} ({$release->name})");

        // Find tenants without a current version
        $tenantsWithoutVersion = Tenant::whereDoesntHave('updates', function ($query) {
            $query->where('is_current', true);
        })->get();

        if ($tenantsWithoutVersion->isEmpty()) {
            $this->info('✓ All tenants already have a current version assigned.');
        } else {
            $this->info("Found {$tenantsWithoutVersion->count()} tenant(s) without a current version.");

            foreach ($tenantsWithoutVersion as $tenant) {
                $tenant->updates()->updateOrCreate(
                    ['app_release_id' => $release->id],
                    [
                        'status' => 'up_to_date',
                        'is_current' => true,
                        'action_taken_at' => now(),
                    ]
                );

                $this->line("  Assigned {$release->version_tag} to {$tenant->id}");
            }

            $this->info("✓ Initialized {$tenantsWithoutVersion->count()} tenant(s).");
        }

        // Clean up orphaned update_available records
        if ($this->option('cleanup')) {
            $deleted = TenantUpdate::where('status', 'update_available')
                ->where('is_current', false)
                ->delete();

            $this->info("✓ Cleaned up {$deleted} stale 'update_available' record(s).");
        }

        // Re-notify tenants so only genuinely newer versions appear
        $releaseService->notifyTenantsOfUpdates();
        $this->info('✓ Tenant update notifications refreshed.');

        return Command::SUCCESS;
    }
}
