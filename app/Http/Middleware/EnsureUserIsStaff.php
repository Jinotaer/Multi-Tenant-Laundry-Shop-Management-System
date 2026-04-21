<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStaff
{
    /**
     * Ensure user is staff (not customer).
     * Allows any User model (owner, staff, manager, supervisor, etc.)
     * Blocks Customer model.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        // Block customers from staff routes
        if ($user instanceof \App\Models\Customer) {
            abort(403, 'Unauthorized.');
        }

        // Allow all User model instances (any role)
        return $next($request);
    }
}
