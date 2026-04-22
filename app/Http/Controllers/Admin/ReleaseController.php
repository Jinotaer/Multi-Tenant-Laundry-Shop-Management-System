<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Models\Tenant;
use App\Models\TenantUpdate;
use App\Services\GitHubReleaseService;

class ReleaseController extends Controller
{
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

        return view('admin.releases.show', compact('release', 'tenantUpdates'));
    }
}
