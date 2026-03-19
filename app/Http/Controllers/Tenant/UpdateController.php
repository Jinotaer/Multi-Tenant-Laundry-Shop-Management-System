<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Models\TenantUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateController extends Controller
{
    /**
     * Display the version center for the tenant.
     */
    public function index()
    {
        $tenant = tenant();
        
        $currentVersion = $tenant->currentVersion();
        
        $updates = $tenant->updates()
            ->with('release')
            ->orderByDesc('created_at')
            ->get();
            
        $availableUpdates = $updates->where('status', 'update_available')->where('is_current', false);
        $updateHistory = $updates->whereNotIn('status', ['update_available', 'deferred']);

        return view('tenant.updates.index', compact('currentVersion', 'availableUpdates', 'updateHistory'));
    }

    /**
     * Apply an update to the current tenant.
     */
    public function update(Request $request, AppRelease $release)
    {
        $tenant = tenant();

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
                'action_taken_at' => now()
            ]
        );

        return back()->with('success', 'Successfully updated your application version to ' . $release->version_tag);
    }

    /**
     * Rollback to a previous release.
     */
    public function rollback(Request $request, AppRelease $release)
    {
        $tenant = tenant();

        // Safety check - maybe we limit rollbacks to within 30 days
        // Or ensure the target release isn't a completely incompatible DB state
        
        $tenant->updates()->where('is_current', true)->update(['is_current' => false]);

        $tenant->updates()->updateOrCreate(
            [
                'app_release_id' => $release->id,
            ],
            [
                'status' => 'rolled_back',
                'is_current' => true,
                'action_taken_at' => now()
            ]
        );

        return back()->with('success', 'Rolled back to version ' . $release->version_tag);
    }
}
