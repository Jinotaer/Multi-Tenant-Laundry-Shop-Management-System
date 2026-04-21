<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Billing Cycle Tracking (for efficient querying)
            $table->timestamp('billing_cycle_start')->nullable()->after('created_at');
            $table->timestamp('billing_cycle_end')->nullable()->after('billing_cycle_start');
            
            // Add index for fast cycle-based queries
            $table->index(['billing_cycle_start', 'billing_cycle_end'], 'idx_billing_cycle');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_billing_cycle');
            $table->dropColumn(['billing_cycle_start', 'billing_cycle_end']);
        });
    }
};
