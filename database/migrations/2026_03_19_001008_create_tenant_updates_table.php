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
        Schema::create('tenant_updates', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('app_release_id')->constrained('app_releases')->cascadeOnDelete(); // Target or current release
            $table->string('status')->default('up_to_date'); // up_to_date, update_available, deferred, updating, updated, rolled_back
            $table->boolean('is_current')->default(false); // Indicates the active version
            $table->timestamp('action_taken_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_updates');
    }
};
