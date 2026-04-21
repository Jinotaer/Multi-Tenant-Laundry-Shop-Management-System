<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Billing Cycle Tracking
            $table->timestamp('current_cycle_start')->nullable()->after('subscription_expires_at');
            $table->timestamp('current_cycle_end')->nullable()->after('current_cycle_start');
            
            // Pending Plan Change (scheduled for next cycle)
            $table->unsignedBigInteger('pending_plan_id')->nullable()->after('subscription_plan_id');
            $table->timestamp('pending_plan_scheduled_at')->nullable()->after('pending_plan_id');
            
            // Add foreign key for pending plan
            $table->foreign('pending_plan_id')->references('id')->on('subscription_plans')->onDelete('set null');
            
            // Add indexes for performance
            $table->index(['current_cycle_start', 'current_cycle_end'], 'idx_cycle_dates');
            $table->index(['pending_plan_id', 'pending_plan_scheduled_at'], 'idx_pending_plan');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['pending_plan_id']);
            $table->dropIndex('idx_cycle_dates');
            $table->dropIndex('idx_pending_plan');
            $table->dropColumn([
                'current_cycle_start',
                'current_cycle_end',
                'pending_plan_id',
                'pending_plan_scheduled_at'
            ]);
        });
    }
};
