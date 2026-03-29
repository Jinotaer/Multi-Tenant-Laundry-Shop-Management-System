<?php

// Add this temporarily to routes/tenant.php for debugging

Route::get('/debug-tenant', function() {
    $tenant = tenant();
    
    // Get fresh data from database
    $freshTenant = \App\Models\Tenant::find($tenant->id);
    
    return response()->json([
        'tenant_id' => $tenant->id,
        'subscription_plan_id' => $tenant->subscription_plan_id,
        'features' => $tenant->features,
        'is_paid' => $tenant->is_paid,
        'plan_name' => $tenant->subscriptionPlan?->name,
        'plan_features' => $tenant->subscriptionPlan?->features,
        'fresh_features' => $freshTenant->features,
        'fresh_plan_id' => $freshTenant->subscription_plan_id,
    ]);
})->middleware(['web', \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class]);
