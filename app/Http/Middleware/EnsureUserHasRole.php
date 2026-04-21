<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        // Allow customers to only access customer routes
        if ($user instanceof \App\Models\Customer) {
            if (! in_array('customer', $roles, true)) {
                abort(403, 'Unauthorized.');
            }
            return $next($request);
        }

        // For User model: check role column or roles relationship
        if (method_exists($user, 'hasRole') && $user->hasRole($roles)) {
            return $next($request);
        }

        abort(403, 'Unauthorized.');
    }
}
