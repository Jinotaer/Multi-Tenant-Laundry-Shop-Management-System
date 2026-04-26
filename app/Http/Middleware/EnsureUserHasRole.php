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

        // Any User model (staff, owner, or even role=customer) accessing a customer-only
        // route must be redirected to the admin area, not allowed into the customer portal.
        // This must run before hasRole() because a User with role='customer' would otherwise
        // pass the hasRole check, reach the controller as a non-Customer instance, and 403.
        if (in_array('customer', $roles, true)) {
            $order = $request->route('order');
            if ($order) {
                $orderId = is_object($order) ? $order->id : $order;
                return redirect()->route('tenant.orders.show', $orderId);
            }

            return redirect()->route('tenant.dashboard');
        }

        // For non-customer routes: check role column or roles relationship
        if (method_exists($user, 'hasRole') && $user->hasRole($roles)) {
            return $next($request);
        }

        abort(403, 'Unauthorized.');
    }
}
