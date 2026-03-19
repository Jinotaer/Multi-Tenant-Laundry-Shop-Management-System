<?php

namespace App\Services;

use App\Models\AppRelease;
use App\Models\Tenant;
use App\Models\TenantUpdate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubReleaseService
{
    /**
     * Sync releases from a GitHub repository to the local database.
     */
    public function syncReleases()
    {
        $repo = config('services.github.repo'); // e.g. "username/repo"
        $token = config('services.github.token');

        if (!$repo) {
            Log::warning('GitHub repository not configured for Release Sync.');
            return false;
        }

        $request = Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
        ]);

        if ($token) {
            $request->withToken($token);
        }

        $response = $request->get("https://api.github.com/repos/{$repo}/releases");

        if ($response->successful()) {
            $releases = $response->json();

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
            }
            
            $this->notifyTenantsOfUpdates();
            return true;
        }

        Log::error('Failed to sync GitHub Releases', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);
        
        return false;
    }

    /**
     * Automatically create 'update_available' records for tenants
     * who don't have the latest full release.
     */
    public function notifyTenantsOfUpdates()
    {
        $latestRelease = AppRelease::where('is_prerelease', false)->orderBy('published_at', 'desc')->first();

        if (!$latestRelease) {
            return;
        }

        // Find tenants who aren't currently on this release
        // (meaning their 'is_current' = true record isn't for this release)
        $tenantsToNotify = Tenant::whereDoesntHave('updates', function ($query) use ($latestRelease) {
            $query->where('app_release_id', $latestRelease->id)->where('is_current', true);
        })->get();

        foreach ($tenantsToNotify as $tenant) {
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
            
            // Optional: You could trigger an Event or Mail notification here
            // e.g. Mail::to($tenant->owner_email)->send(new NewVersionAvailable($latestRelease));
        }
    }
}
