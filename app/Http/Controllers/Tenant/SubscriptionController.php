<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    /**
     * Show the tenant's current subscription plan and usage.
     */
    public function index(): View
    {
        $tenant = tenant();
        $tenant->load('subscriptionPlan');

        $service = new PlanLimitService($tenant);

        $staffCount = User::where('role', '!=', 'owner')->count();
        $customerCount = Schema::hasTable('customers')
            ? DB::table('customers')->count()
            : 0;
        $monthlyOrderCount = Schema::hasTable('orders')
            ? DB::table('orders')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count()
            : 0;

        $usage = $service->getUsageSummary($staffCount, $customerCount, $monthlyOrderCount);

        $allFeatures = config('themes.features', []);

        return view('tenant.subscription', [
            'plan' => $tenant->subscriptionPlan,
            'usage' => $usage,
            'allFeatures' => $allFeatures,
            'tenantFeatures' => $tenant->features ?? [],
            'isOnTrial' => $tenant->isOnTrial(),
            'isPaid' => $tenant->is_paid,
            'trialEndsAt' => $tenant->trial_ends_at,
            'trialDaysRemaining' => $tenant->trialDaysRemaining(),
        ]);
    }
}
