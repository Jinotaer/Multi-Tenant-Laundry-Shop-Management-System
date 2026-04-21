<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AppRelease;
use App\Models\Tenant;
use App\Models\TenantUpdate;
use App\Notifications\AdminGenericNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubReleaseService
{
    private const CACHE_KEY = 'github_releases_last_sync';

    private const RATE_LIMIT_CACHE_KEY = 'github_rate_limit_reset';

    private const SYNC_INTERVAL_MINUTES = 60; // Check every hour

    /**
     * Sync releases from a GitHub repository to the local database.
     */
    public function syncReleases(bool $force = false)
    {
        $repo = config('services.github.repo'); // e.g. "username/repo"
        $token = config('services.github.token');

        if (! $repo) {
            Log::warning('GitHub repository not configured for Release Sync.');

            return false;
        }

        // Check if we're rate limited
        if ($this->isRateLimited()) {
            $resetTime = Cache::get(self::RATE_LIMIT_CACHE_KEY);
            Log::warning('GitHub API rate limited. Resets at: '.$resetTime);

            return false;
        }

        // Check if we synced recently (unless forced)
        if (! $force && $this->syncedRecently()) {
            Log::info('GitHub releases synced recently. Skipping.');

            return true;
        }

        $request = Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
        ]);

        if ($token) {
            $request->withToken($token);
        }

        $response = $request->get("https://api.github.com/repos/{$repo}/releases");

        // Handle rate limiting
        if ($response->status() === 403 && $response->header('X-RateLimit-Remaining') === '0') {
            $resetTime = $response->header('X-RateLimit-Reset');
            Cache::put(self::RATE_LIMIT_CACHE_KEY, $resetTime, now()->addHours(2));
            Log::warning('GitHub API rate limit exceeded. Resets at: '.date('Y-m-d H:i:s', $resetTime));

            return false;
        }

        if ($response->successful()) {
            $releases = $response->json();
            $newReleases = [];

            foreach ($releases as $releaseData) {
                $release = AppRelease::updateOrCreate(
                    ['version_tag' => $releaseData['tag_name']],
                    [
                        'name' => $releaseData['name'] ?? $releaseData['tag_name'],
                        'body' => $releaseData['body'],
                        'is_prerelease' => $releaseData['prerelease'],
                        'published_at' => \Carbon\Carbon::parse($releaseData['published_at']),
                    ]
                );

                // Track newly created releases
                if ($release->wasRecentlyCreated) {
                    $newReleases[] = $release;
                }
            }

            // Update last sync time
            Cache::put(self::CACHE_KEY, now(), now()->addDay());

            // Notify tenants of new updates
            $this->notifyTenantsOfUpdates();

            // Notify admins of new releases
            if (! empty($newReleases)) {
                $this->notifyAdminsOfNewReleases($newReleases);
            }

            return true;
        }

        Log::error('Failed to sync GitHub Releases', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    /**
     * Check if we're currently rate limited.
     */
    private function isRateLimited(): bool
    {
        $resetTime = Cache::get(self::RATE_LIMIT_CACHE_KEY);

        if (! $resetTime) {
            return false;
        }

        return now()->timestamp < $resetTime;
    }

    /**
     * Check if we synced recently.
     */
    private function syncedRecently(): bool
    {
        $lastSync = Cache::get(self::CACHE_KEY);

        if (! $lastSync) {
            return false;
        }

        return $lastSync->addMinutes(self::SYNC_INTERVAL_MINUTES)->isFuture();
    }

    /**
     * Automatically create 'update_available' records for tenants
     * who don't have the latest full release.
     */
    public function notifyTenantsOfUpdates()
    {
        $latestRelease = AppRelease::where('is_prerelease', false)
            ->orderBy('published_at', 'desc')
            ->first();

        if (! $latestRelease) {
            return;
        }

        // Find tenants who aren't currently on this release
        $tenantsToNotify = Tenant::whereDoesntHave('updates', function ($query) use ($latestRelease) {
            $query->where('app_release_id', $latestRelease->id)->where('is_current', true);
        })->get();

        foreach ($tenantsToNotify as $tenant) {
            // Get tenant's current version
            $currentUpdate = $tenant->updates()->where('is_current', true)->with('release')->first();
            $currentVersion = $currentUpdate?->release?->version_tag ?? 'v0.0.0';

            // Only notify if the new version is actually newer
            if ($this->isNewerVersion($latestRelease->version_tag, $currentVersion)) {
                // Create an 'update_available' record if they don't already have one for this release
                TenantUpdate::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'app_release_id' => $latestRelease->id,
                    ],
                    [
                        'status' => 'update_available',
                        'is_current' => false,
                    ]
                );
            }
        }
    }

    /**
     * Notify admins of new releases.
     */
    private function notifyAdminsOfNewReleases(array $releases)
    {
        $releaseNames = collect($releases)->pluck('version_tag')->join(', ');

        Admin::on('mysql')->get()->each(function (Admin $admin) use ($releaseNames) {
            $admin->notify(new AdminGenericNotification(
                "New app releases available: {$releaseNames}"
            ));
        });
    }

    /**
     * Assign the latest stable version to a new tenant.
     */
    public function assignLatestVersionToTenant(Tenant $tenant): void
    {
        $latestStableRelease = AppRelease::where('is_prerelease', false)
            ->orderByDesc('published_at')
            ->first();

        if (! $latestStableRelease) {
            // Create a default v1.0.0 release if none exists
            $latestStableRelease = AppRelease::create([
                'version_tag' => 'v1.0.0',
                'name' => 'Initial Release',
                'body' => 'Initial application version',
                'is_prerelease' => false,
                'is_required' => false,
                'published_at' => now(),
            ]);
        }

        // Assign this version to the tenant
        $tenant->updates()->create([
            'app_release_id' => $latestStableRelease->id,
            'status' => 'up_to_date',
            'is_current' => true,
            'action_taken_at' => now(),
        ]);

        Log::info("Assigned version {$latestStableRelease->version_tag} to tenant {$tenant->id}");
    }

    /**
     * Compare two semantic versions.
     * Returns true if $version1 is newer than $version2.
     */
    public function isNewerVersion(string $version1, string $version2): bool
    {
        $v1 = $this->normalizeVersion($version1);
        $v2 = $this->normalizeVersion($version2);

        return version_compare($v1, $v2, '>');
    }

    /**
     * Check if a rollback to a specific version is safe.
     */
    public function canRollbackTo(AppRelease $release, Tenant $tenant): array
    {
        $errors = [];
        $warnings = [];

        // Check if release is too old (more than 90 days)
        if ($release->published_at->lt(now()->subDays(90))) {
            $errors[] = 'This release is too old (more than 90 days). Rollback may cause compatibility issues.';
        }

        // Check if tenant has ever used this version
        $hasUsedVersion = $tenant->updates()
            ->where('app_release_id', $release->id)
            ->exists();

        if (! $hasUsedVersion) {
            $warnings[] = 'You have never used this version before. Review your backup before proceeding.';
        }

        // Get current version
        $currentUpdate = $tenant->updates()->where('is_current', true)->with('release')->first();

        if ($currentUpdate) {
            $currentVersion = $this->normalizeVersion($currentUpdate->release->version_tag);
            $targetVersion = $this->normalizeVersion($release->version_tag);

            // Check if trying to rollback to a newer version
            if (version_compare($targetVersion, $currentVersion, '>=')) {
                $errors[] = 'Cannot rollback to a version that is the same or newer than your current version.';
            }

            // Check major version difference
            $currentMajor = (int) explode('.', $currentVersion)[0];
            $targetMajor = (int) explode('.', $targetVersion)[0];

            if ($currentMajor - $targetMajor > 1) {
                $errors[] = 'Rolling back more than one major version may cause database compatibility issues.';
            }
        }

        return [
            'can_rollback' => empty($errors),
            'errors' => $errors,
            'warnings' => empty($errors)
                ? array_values(array_unique([...$warnings, 'Please ensure you have a recent backup before proceeding.']))
                : [],
        ];
    }

    /**
     * Normalize a semantic version tag for comparisons.
     */
    public function normalizeVersion(string $version): string
    {
        return preg_replace('/^v/i', '', trim($version));
    }
}
