<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckRequiredUpdate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip if not in tenant context
        if (!tenancy()->initialized) {
            return $next($request);
        }
        
        $tenant = tenant();
        
        if (!$tenant) {
            return $next($request);
        }
        
        try {
            // Get current version
            $currentUpdate = $tenant->updates()
                ->where('is_current', true)
                ->with('release')
                ->first();
            
            if (!$currentUpdate || !$currentUpdate->release) {
                return $next($request);
            }
            
            // Check if there's a required update available
            $requiredUpdate = \App\Models\AppRelease::where('is_required', true)
                ->where('published_at', '>', $currentUpdate->release->published_at)
                ->orderBy('published_at', 'asc')
                ->first();
            
            if (!$requiredUpdate) {
                return $next($request);
            }
            
            // Check if grace period has expired
            $gracePeriodDays = config('updates.required_update_grace_period', 7);
            $gracePeriodExpired = $requiredUpdate->published_at
                ->addDays($gracePeriodDays)
                ->isPast();
            
            // Allow access to update center and logout
            $allowedRoutes = [
                'tenant.updates.index',
                'tenant.updates.apply',
                'tenant.logout',
                'tenant.settings.profile'
            ];
            
            if (in_array($request->route()->getName(), $allowedRoutes)) {
                return $next($request);
            }
            
            // If grace period expired, force update
            if ($gracePeriodExpired) {
                Log::warning("Tenant {$tenant->id} blocked due to required update", [
                    'current_version' => $currentUpdate->release->version_tag,
                    'required_version' => $requiredUpdate->version_tag
                ]);
                
                return redirect()
                    ->route('tenant.updates.index')
                    ->with('error', 
                        "A critical update to version {$requiredUpdate->version_tag} is required. " .
                        "Please update immediately to continue using the application."
                    );
            }
            
            // Show warning during grace period
            if (!session()->has('update_warning_shown')) {
                $daysRemaining = now()->diffInDays($requiredUpdate->published_at->addDays($gracePeriodDays));
                
                session()->flash('warning', 
                    "A required update to version {$requiredUpdate->version_tag} is available. " .
                    "You have {$daysRemaining} days remaining to update before access is restricted."
                );
                
                session()->put('update_warning_shown', true);
            }
            
        } catch (\Exception $e) {
            Log::error('CheckRequiredUpdate middleware error', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id ?? 'unknown'
            ]);
        }
        
        return $next($request);
    }
}
