<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('permissions')
            ->whereIn('key', [
                'orders.view',
                'orders.create',
                'orders.update',
                'orders.status_update',
                'orders.mark_paid',
            ])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->upsert([
            [
                'key' => 'orders.view',
                'label' => 'View Orders',
                'module' => 'orders',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'orders.create',
                'label' => 'Create Orders',
                'module' => 'orders',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'orders.update',
                'label' => 'Update Orders',
                'module' => 'orders',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'orders.status_update',
                'label' => 'Update Order Status',
                'module' => 'orders',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'orders.mark_paid',
                'label' => 'Mark Orders as Paid',
                'module' => 'orders',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['key'], ['label', 'module', 'updated_at']);
    }
};
