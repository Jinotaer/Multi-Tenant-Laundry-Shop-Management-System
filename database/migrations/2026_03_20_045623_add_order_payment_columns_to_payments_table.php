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
            if (! Schema::hasColumn('payments', 'payment_type')) {
                $table->string('payment_type')->default('subscription')->after('subscription_plan_id')->index();
            }

            if (! Schema::hasColumn('payments', 'tenant_order_id')) {
                $table->unsignedBigInteger('tenant_order_id')->nullable()->after('payment_type')->index();
            }

            if (! Schema::hasColumn('payments', 'checkout_url')) {
                $table->text('checkout_url')->nullable()->after('paymongo_payment_id');
            }

            if (! Schema::hasColumn('payments', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('description');
            }

            if (! Schema::hasColumn('payments', 'customer_email')) {
                $table->string('customer_email')->nullable()->after('customer_name');
            }

            if (! Schema::hasColumn('payments', 'metadata')) {
                $table->json('metadata')->nullable()->after('customer_email');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_type') && ! Schema::hasIndex('payments', ['payment_type'])) {
                $table->index('payment_type');
            }

            if (Schema::hasColumn('payments', 'tenant_order_id') && ! Schema::hasIndex('payments', ['tenant_order_id'])) {
                $table->index('tenant_order_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasIndex('payments', ['payment_type'])) {
                $table->dropIndex(['payment_type']);
            }

            if (Schema::hasIndex('payments', ['tenant_order_id'])) {
                $table->dropIndex(['tenant_order_id']);
            }
        });

        $columns = array_values(array_filter([
            'payment_type',
            'tenant_order_id',
            'checkout_url',
            'customer_name',
            'customer_email',
            'metadata',
        ], fn (string $column): bool => Schema::hasColumn('payments', $column)));

        if ($columns !== []) {
            Schema::table('payments', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
