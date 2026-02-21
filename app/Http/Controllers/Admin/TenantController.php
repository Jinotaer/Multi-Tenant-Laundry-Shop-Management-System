<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    /**
     * Display a listing of all tenants.
     */
    public function index(): View
    {
        // Only show tenants that were created via an approved registration.  This
        // prevents hand‑created or orphaned tenants (e.g. from failed/no approval)
        // from appearing in the list. Having the filter here keeps the behaviour
        // consistent with the admin's expectations.
        $tenants = Tenant::with(['domains', 'registration', 'subscriptionPlan'])
            ->whereHas('registration', fn ($q) => $q->where('status', 'approved'))
            ->latest()
            ->paginate(15);

        return view('admin.tenants.index', [
            'tenants' => $tenants,
        ]);
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant): View
    {
        $tenant->load(['domains', 'registration', 'subscriptionPlan']);
        $plans = SubscriptionPlan::active()->orderBy('sort_order')->get();

        return view('admin.tenants.show', [
            'tenant' => $tenant,
            'plans' => $plans,
        ]);
    }

    /**
     * Toggle tenant enabled/disabled status.
     */
    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_enabled' => ! $tenant->is_enabled]);

        $status = $tenant->is_enabled ? 'enabled' : 'disabled';
        $shopName = $tenant->data['shop_name'] ?? $tenant->id;

        return redirect()->back()
            ->with('success', "Shop '{$shopName}' has been {$status}.");
    }

    /**
     * Remove the specified tenant.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenant->delete();

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }

    /**
     * Update feature flags for a tenant.
     */
    public function updateFeatures(Request $request, Tenant $tenant): RedirectResponse
    {
        $validFeatures = array_keys(config('themes.features', []));

        $request->validate([
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'in:'.implode(',', $validFeatures)],
        ]);

        $tenant->features = $request->input('features', []);
        $tenant->save();

        $shopName = $tenant->data['shop_name'] ?? $tenant->id;

        return back()->with('success', "Feature flags for '{$shopName}' updated successfully.");
    }

    /**
     * Update the subscription plan for a tenant.
     */
    public function updatePlan(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
        ]);

        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

        $tenant->subscription_plan_id = $plan->id;
        $tenant->features = $plan->features ?? [];
        $tenant->save();

        $shopName = $tenant->data['shop_name'] ?? $tenant->id;

        return back()->with('success', "Plan for '{$shopName}' updated to '{$plan->name}'.");
    }

    /**
     * Mark a tenant as paid (removes trial restrictions).
     */
    public function markPaid(Tenant $tenant): RedirectResponse
    {
        $tenant->update([
            'is_paid' => true,
            'is_enabled' => true,
        ]);

        $shopName = $tenant->data['shop_name'] ?? $tenant->id;

        return back()->with('success', "'{$shopName}' has been marked as paid.");
    }

    /**
     * Revoke paid status from a tenant.
     */
    public function markUnpaid(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_paid' => false]);

        $shopName = $tenant->data['shop_name'] ?? $tenant->id;

        return back()->with('success', "Paid status revoked for '{$shopName}'.");
    }
}
