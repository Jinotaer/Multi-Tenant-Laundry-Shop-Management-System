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
        Schema::create('tenant_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->decimal('database_size_mb', 10, 2)->default(0);
            $table->decimal('storage_size_mb', 10, 2)->default(0);
            $table->unsignedBigInteger('api_requests_count')->default(0);
            $table->decimal('bandwidth_mb', 10, 2)->default(0);
            $table->unsignedInteger('active_users_count')->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('customers_count')->default(0);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->index(['tenant_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_metrics');
    }
};
