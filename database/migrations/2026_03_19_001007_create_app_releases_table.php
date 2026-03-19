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
        Schema::create('app_releases', function (Blueprint $table) {
            $table->id();
            $table->string('version_tag')->unique(); // e.g., 'v1.2.0'
            $table->string('name'); // Release title
            $table->text('body')->nullable(); // Release notes
            $table->boolean('is_prerelease')->default(false);
            $table->boolean('is_required')->default(false); // Forced update?
            $table->timestamp('published_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};
