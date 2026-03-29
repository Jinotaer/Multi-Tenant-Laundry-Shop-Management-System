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
            $table->unsignedInteger('loyalty_points_redeemed')->default(0)->after('loyalty_points_awarded_at');
            $table->decimal('loyalty_discount_amount', 10, 2)->default(0)->after('loyalty_points_redeemed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'loyalty_points_redeemed',
                'loyalty_discount_amount',
            ]);
        });
    }
};
