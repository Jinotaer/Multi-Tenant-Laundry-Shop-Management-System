<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackTenantUsage
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track for tenant requests
        $tenant = tenant();
        if (! $tenant) {
            return $response;
        }

        $this->incrementApiRequest($tenant->id);

        $this->incrementBandwidth($tenant->id, $this->resolveResponseSizeBytes($response));

        return $response;
    }

    /**
     * Increment API request count for a tenant.
     */
    protected function incrementApiRequest(string $tenantId): void
    {
        try {
            \App\Models\Tenant::where('id', $tenantId)->increment('current_api_requests', 1);
        } catch (\Throwable $e) {
            Log::warning("Failed to increment API requests for tenant {$tenantId}: ".$e->getMessage());
        }
    }

    /**
     * Estimate response size in bytes.
     */
    protected function resolveResponseSizeBytes(Response $response): int
    {
        $contentLengthHeader = $response->headers->get('Content-Length');
        if (is_numeric($contentLengthHeader)) {
            return max(0, (int) $contentLengthHeader);
        }

        $content = $response->getContent();
        if (! is_string($content)) {
            return 0;
        }

        return strlen($content);
    }

    /**
     * Increment bandwidth usage in MB.
     */
    protected function incrementBandwidth(string $tenantId, int $bytes): void
    {
        if ($bytes <= 0) {
            return;
        }

        try {
            $mb = $bytes / 1024 / 1024;
            \App\Models\Tenant::where('id', $tenantId)->increment('current_bandwidth_mb', $mb);
        } catch (\Throwable $e) {
            Log::warning("Failed to increment bandwidth for tenant {$tenantId}: ".$e->getMessage());
        }
    }
}
