<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTrialStatus
{
    /**
     * Handle an incoming request.
     *
     * Block access when the tenant's trial has expired and they haven't paid,
     * or when a premium tenant hasn't completed payment yet.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (! $tenant) {
            return $next($request);
        }

        // If tenant has paid or is still on trial, allow access.
        if ($tenant->hasActiveSubscription()) {
            return $next($request);
        }

        // If trial has expired and not paid, block access (except trial-expired page & logout).
        if ($tenant->isTrialExpired()) {
            $allowedRoutes = ['tenant.trial-expired', 'tenant.logout'];

            if (in_array($request->route()?->getName(), $allowedRoutes)) {
                return $next($request);
            }

            return redirect()->route('tenant.trial-expired');
        }

        // No trial set and not paid — premium plan that hasn't paid yet.
        if (is_null($tenant->trial_ends_at) && ! $tenant->is_paid) {
            return redirect()->route('tenant.payment.show');
        }

        // Legacy tenant with no trial set — allow access.
        return $next($request);
    }
}
