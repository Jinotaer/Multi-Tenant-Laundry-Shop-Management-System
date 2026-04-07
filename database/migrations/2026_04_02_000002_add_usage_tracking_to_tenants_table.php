<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->decimal('current_storage_mb', 10, 2)->default(0)->after('is_paid');
            $table->decimal('current_bandwidth_mb', 10, 2)->default(0)->after('current_storage_mb');
            $table->unsignedBigInteger('current_api_requests')->default(0)->after('current_bandwidth_mb');
            $table->decimal('storage_limit_mb', 10, 2)->nullable()->after('current_api_requests');
            $table->decimal('bandwidth_limit_mb', 10, 2)->nullable()->after('storage_limit_mb');
            $table->timestamp('usage_reset_at')->nullable()->after('bandwidth_limit_mb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'current_storage_mb',
                'current_bandwidth_mb',
                'current_api_requests',
                'storage_limit_mb',
                'bandwidth_limit_mb',
                'usage_reset_at',
            ]);
        });
    }
};
