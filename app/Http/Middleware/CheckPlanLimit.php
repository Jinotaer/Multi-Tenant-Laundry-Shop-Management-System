<?php

namespace App\Http\Middleware;

use App\Services\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimit
{
    /**
     * Handle an incoming request.
     *
     * Check plan limits for staff, customers, or orders.
     * Usage: middleware('plan.limit:staff,5') where 5 is current count,
     * or middleware('plan.limit:staff') to let the middleware resolve the count.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $resource  The resource type: staff, customer, or order
     */
    public function handle(Request $request, Closure $next, string $resource = 'staff'): Response
    {
        $tenant = tenant();

        if (! $tenant) {
            return $next($request);
        }

        $service = new PlanLimitService($tenant);
        $plan = $service->plan();

        if (! $plan) {
            return $next($request);
        }

        $allowed = match ($resource) {
            'staff' => $service->canAddStaff($this->getCount($tenant, 'staff')),
            'customer' => $service->canAddCustomer($this->getCount($tenant, 'customer')),
            'order' => $service->canAddOrder($this->getCount($tenant, 'order')),
            default => true,
        };

        if (! $allowed) {
            $limitName = ucfirst($resource);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "{$limitName} limit reached for your current plan ({$plan->name}). Please upgrade your plan.",
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->back()->with('error', "{$limitName} limit reached for your current plan ({$plan->name}). Please upgrade your plan.");
        }

        return $next($request);
    }

    /**
     * Get the current count for a resource type.
     * Uses the tenant's database to count records.
     */
    protected function getCount($tenant, string $resource): int
    {
        return match ($resource) {
            'staff' => \App\Models\User::where('role', '!=', 'owner')->count(),
            'customer' => \DB::table('customers')->count(),
            'order' => \DB::table('orders')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            default => 0,
        };
    }
}
