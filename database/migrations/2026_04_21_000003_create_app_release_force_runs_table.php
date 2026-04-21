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
        Schema::create('app_release_force_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_release_id')->constrained('app_releases')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('status', 20)->default('running');
            $table->boolean('deployment_success')->default(false);
            $table->text('deployment_error')->nullable();
            $table->unsignedInteger('total_tenants')->default(0);
            $table->unsignedInteger('successful_tenants')->default(0);
            $table->unsignedInteger('failed_tenants')->default(0);
            $table->json('failed_tenant_ids')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_release_force_runs');
    }
};
