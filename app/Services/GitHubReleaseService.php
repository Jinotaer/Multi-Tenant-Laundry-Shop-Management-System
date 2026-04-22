<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AppRelease;
use App\Models\Tenant;
use App\Models\TenantUpdate;
use Illuminate\Support\Collection;
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
        $latestRelease = $this->latestStableRelease();

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
        $latestStableRelease = $this->latestStableRelease();

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
     * Normalize a semantic version tag for comparisons.
     */
    public function normalizeVersion(string $version): string
    {
        $normalized = trim($version);

        // Accept common tag prefixes like v1.2.3 and v.1.2.3.
        $normalized = preg_replace('/^v[\.\-_]*/i', '', $normalized);

        // Remove any remaining non-numeric prefix so version_compare works reliably.
        $normalized = preg_replace('/^[^0-9]+/', '', $normalized);

        return $normalized !== '' ? $normalized : '0.0.0';
    }

    /**
     * Compare two version tags using semantic version ordering.
     */
    public function compareVersions(string $left, string $right): int
    {
        return version_compare(
            $this->normalizeVersion($left),
            $this->normalizeVersion($right)
        );
    }

    /**
     * Sort releases from newest semantic version to oldest.
     *
     * @param  iterable<int, AppRelease>  $releases
     * @return Collection<int, AppRelease>
     */
    public function sortReleasesDescending(iterable $releases): Collection
    {
        return collect($releases)
            ->sort(fn (AppRelease $left, AppRelease $right): int => $this->compareVersions(
                $right->version_tag,
                $left->version_tag
            ))
            ->values();
    }

    /**
     * Resolve the latest stable release by semantic version, not publish date.
     */
    public function latestStableRelease(): ?AppRelease
    {
        return $this->sortReleasesDescending(
            AppRelease::where('is_prerelease', false)->get()
        )->first();
    }
}
