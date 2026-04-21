<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Map old permission keys to new ones
        $permissionMap = [
            'dashboard.view' => 'dashboard.access',
            'orders.view' => 'orders.access',
            'customers.view' => 'customers.access',
            'staff.view' => 'staff.access',
            'roles.view' => 'roles.access',
            'services.view' => 'services.access',
            'expenses.view' => 'expenses.access',
            'inventory.view' => 'inventory.access',
            'reports.view' => 'reports.access',
            'analytics.view' => 'analytics.access',
            'billing.view' => 'billing.access',
            'updates.view' => 'updates.access',
        ];

        foreach ($permissionMap as $oldKey => $newKey) {
            DB::table('permissions')
                ->where('key', $oldKey)
                ->update(['key' => $newKey]);
        }
    }

    public function down(): void
    {
        // Reverse the mapping
        $permissionMap = [
            'dashboard.access' => 'dashboard.view',
            'orders.access' => 'orders.view',
            'customers.access' => 'customers.view',
            'staff.access' => 'staff.view',
            'roles.access' => 'roles.view',
            'services.access' => 'services.view',
            'expenses.access' => 'expenses.view',
            'inventory.access' => 'inventory.view',
            'reports.access' => 'reports.view',
            'analytics.access' => 'analytics.view',
            'billing.access' => 'billing.view',
            'updates.access' => 'updates.view',
        ];

        foreach ($permissionMap as $newKey => $oldKey) {
            DB::table('permissions')
                ->where('key', $newKey)
                ->update(['key' => $oldKey]);
        }
    }
};
