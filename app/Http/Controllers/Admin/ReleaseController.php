<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Models\TenantUpdate;
use App\Models\Tenant;
use App\Services\GitHubReleaseService;
use Illuminate\Http\Request;

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
        $success = $service->syncReleases();
        
        if ($success) {
            return back()->with('success', 'GitHub releases synced successfully!');
        }

        return back()->with('error', 'Failed to sync releases. Check the GitHub service configuration.');
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

    /**
     * Force this release onto all tenants.
     */
    public function forceUpdateAll(Request $request, AppRelease $release)
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Deactivate old current version
            $tenant->updates()->where('is_current', true)->update(['is_current' => false]);

            // Activate new version
            $tenant->updates()->updateOrCreate(
                ['app_release_id' => $release->id],
                [
                    'status' => 'updated',
                    'is_current' => true,
                    'action_taken_at' => now()
                ]
            );
        }
        
        // Mark the release as required globally
        $release->update(['is_required' => true]);

        return back()->with('success', 'Forced ' . $release->version_tag . ' to all tenants.');
    }
}
