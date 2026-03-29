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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_type')->default('subscription')->after('subscription_plan_id')->index();
            $table->unsignedBigInteger('tenant_order_id')->nullable()->after('payment_type')->index();
            $table->text('checkout_url')->nullable()->after('paymongo_payment_id');
            $table->string('customer_name')->nullable()->after('description');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->json('metadata')->nullable()->after('customer_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_type']);
            $table->dropIndex(['tenant_order_id']);
            $table->dropColumn([
                'payment_type',
                'tenant_order_id',
                'checkout_url',
                'customer_name',
                'customer_email',
                'metadata',
            ]);
        });
    }
};
