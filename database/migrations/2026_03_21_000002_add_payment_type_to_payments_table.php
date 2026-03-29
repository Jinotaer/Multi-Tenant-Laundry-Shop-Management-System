<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'payment_type')) {
                $table->string('payment_type')->default('subscription')->after('subscription_plan_id');
            }
            if (!Schema::hasColumn('payments', 'tenant_order_id')) {
                $table->unsignedBigInteger('tenant_order_id')->nullable()->after('payment_type');
            }
            if (!Schema::hasColumn('payments', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('description');
            }
            if (!Schema::hasColumn('payments', 'customer_email')) {
                $table->string('customer_email')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('payments', 'metadata')) {
                $table->json('metadata')->nullable()->after('customer_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'tenant_order_id', 'customer_name', 'customer_email', 'metadata']);
        });
    }
};
