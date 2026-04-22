<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantUpdateMaintenance
{
    /**
     * Block tenant traffic when a tenant-scoped update maintenance mode is active.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! function_exists('tenancy') || ! tenancy()->initialized) {
            return $next($request);
        }

        if (! (bool) config('updates.tenant_maintenance.enabled', true)) {
            return $next($request);
        }

        $tenant = tenant();

        if (! $tenant) {
            return $next($request);
        }

        try {
            $maintenanceActive = $this->maintenanceCache()->has($this->maintenanceCacheKey($tenant->id));
        } catch (\Throwable $e) {
            Log::warning('Unable to read tenant update maintenance state from cache; bypassing maintenance check.', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return $next($request);
        }

        if (! $maintenanceActive) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($this->isAllowedDuringMaintenance($routeName)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'This store is temporarily in maintenance while an update is being finalized.',
            ], 503);
        }

        return redirect()
            ->route('tenant.updates.index')
            ->with('error', 'Your store is currently in maintenance while an update is being finalized.');
    }

    /**
     * Determine if route can be accessed while tenant update maintenance mode is active.
     */
    private function isAllowedDuringMaintenance(?string $routeName): bool
    {
        if ($routeName === null) {
            return false;
        }

        if (str_starts_with($routeName, 'tenant.updates.')) {
            return true;
        }

        return in_array($routeName, ['tenant.logout'], true);
    }

    /**
     * Build cache key for tenant maintenance state.
     */
    private function maintenanceCacheKey($tenantId): string
    {
        return 'tenant:update:maintenance:' . $tenantId;
    }

    /**
     * Resolve cache repository used for tenant update maintenance flags.
     */
    private function maintenanceCache()
    {
        $store = (string) config('updates.tenant_maintenance.cache_store', config('cache.default'));

        return Cache::store($store);
    }
}
