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
            $table->foreignId('service_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->decimal('weight', 8, 2)->nullable()->after('service_id');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid')->after('total_amount');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropColumn(['service_id', 'weight', 'payment_status', 'paid_at']);
        });
    }
};
