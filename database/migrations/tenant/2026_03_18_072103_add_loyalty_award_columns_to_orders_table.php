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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('loyalty_points_awarded')->default(0)->after('payment_status');
            $table->timestamp('loyalty_points_awarded_at')->nullable()->after('loyalty_points_awarded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['loyalty_points_awarded', 'loyalty_points_awarded_at']);
        });
    }
};
