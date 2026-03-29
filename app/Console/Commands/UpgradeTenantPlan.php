<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\TenantFeatureService;
use Illuminate\Console\Command;

class UpgradeTenantPlan extends Command
{
    protected $signature = 'tenant:upgrade {tenant_id} {plan_id}';
    protected $description = 'Manually upgrade a tenant to a new plan';

    public function handle(TenantFeatureService $featureService): int
    {
        $tenantId = $this->argument('tenant_id');
        $planId = $this->argument('plan_id');

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant {$tenantId} not found");
            return 1;
        }

        $plan = SubscriptionPlan::find($planId);
        if (!$plan) {
            $this->error("Plan {$planId} not found");
            return 1;
        }

        $this->info("Current Plan: " . ($tenant->subscriptionPlan?->name ?? 'None'));
        $this->info("Current Features: " . json_encode($tenant->features));
        $this->info("");
        $this->info("New Plan: {$plan->name}");
        $this->info("New Plan Features: " . json_encode($plan->features));

        $normalizedFeatures = $featureService->normalize($plan->features ?? []);
        $this->info("Normalized Features: " . json_encode($normalizedFeatures));

        if (!$this->confirm('Proceed with upgrade?')) {
            return 0;
        }

        $tenant->subscription_plan_id = $plan->id;
        $tenant->features = $normalizedFeatures;
        $tenant->is_paid = true;
        $tenant->is_enabled = true;
        $tenant->subscription_expires_at = now()->addMonth();
        $saved = $tenant->save();

        $this->info("Save result: " . ($saved ? 'SUCCESS' : 'FAILED'));

        // Refresh from database
        $tenant = $tenant->fresh();
        
        $this->info("");
        $this->info("After Upgrade:");
        $this->info("Plan ID: {$tenant->subscription_plan_id}");
        $this->info("Plan Name: " . ($tenant->subscriptionPlan?->name ?? 'None'));
        $this->info("Features: " . json_encode($tenant->features));
        $this->info("Feature Count: " . count($tenant->features ?? []));

        return 0;
    }
}
