<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_history', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            
            $table->unsignedBigInteger('old_plan_id')->nullable();
            $table->unsignedBigInteger('new_plan_id');
            
            $table->enum('change_type', ['initial', 'upgrade', 'downgrade', 'renewal'])->default('renewal');
            
            $table->timestamp('cycle_start')->nullable();
            $table->timestamp('cycle_end')->nullable();
            
            $table->decimal('amount_paid', 10, 2)->default(0);
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('old_plan_id')->references('id')->on('subscription_plans')->onDelete('set null');
            $table->foreign('new_plan_id')->references('id')->on('subscription_plans')->onDelete('cascade');
            
            $table->index(['tenant_id', 'created_at'], 'idx_tenant_history');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_history');
    }
};
