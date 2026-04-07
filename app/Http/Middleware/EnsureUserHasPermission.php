<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$permissions,
    ): Response {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        if ($user->isOwner()) {
            return $next($request);
        }

        if (empty($permissions)) {
            return $next($request);
        }

        if (! method_exists($user, 'hasAnyPermission') || ! $user->hasAnyPermission($permissions)) {
            abort(403, 'Insufficient privileges.');
        }

        return $next($request);
    }
}
