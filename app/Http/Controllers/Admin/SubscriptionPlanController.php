<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use App\Services\TenantFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of all subscription plans.
     */
    public function index(): View
    {
        $plans = SubscriptionPlan::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.subscription-plans.index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Show the form for creating a new plan.
     */
    public function create(): View
    {
        $availableFeatures = config('themes.features', []);

        return view('admin.subscription-plans.create', [
            'availableFeatures' => $availableFeatures,
        ]);
    }

    /**
     * Store a newly created plan.
     */
    public function store(
        SubscriptionPlanRequest $request,
        TenantFeatureService $tenantFeatureService,
    ): RedirectResponse {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);
        $data['features'] = $tenantFeatureService->normalize(
            $request->input('features', []),
        );

        if ($data['is_default']) {
            SubscriptionPlan::where('is_default', true)->update(['is_default' => false]);
        }

        SubscriptionPlan::create($data);

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', "Plan '{$data['name']}' created successfully.");
    }

    /**
     * Show the form for editing a plan.
     */
    public function edit(SubscriptionPlan $plan): View
    {
        $availableFeatures = config('themes.features', []);

        return view('admin.subscription-plans.edit', [
            'plan' => $plan,
            'availableFeatures' => $availableFeatures,
        ]);
    }

    /**
     * Update the specified plan.
     */
    public function update(
        SubscriptionPlanRequest $request,
        SubscriptionPlan $plan,
        TenantFeatureService $tenantFeatureService,
    ): RedirectResponse {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);
        $data['features'] = $tenantFeatureService->normalize(
            $request->input('features', []),
        );

        if ($data['is_default'] && ! $plan->is_default) {
            SubscriptionPlan::where('is_default', true)
                ->where('id', '!=', $plan->id)
                ->update(['is_default' => false]);
        }

        $plan->update($data);

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', "Plan '{$plan->name}' updated successfully.");
    }

    /**
     * Remove the specified plan.
     */
    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        if ($plan->tenants()->exists()) {
            return redirect()->route('admin.subscription-plans.index')
                ->with('error', "Cannot delete '{$plan->name}' — it still has active shops assigned.");
        }

        $name = $plan->name;
        $plan->delete();

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', "Plan '{$name}' deleted successfully.");
    }
}
