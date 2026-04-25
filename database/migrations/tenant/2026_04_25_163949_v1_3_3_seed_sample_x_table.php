<?php

use Database\Seeders\SampleXSeeder;
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
        if (! Schema::hasTable('sample_x')) {
            Schema::create('sample_x', function (Blueprint $table) {
                $table->id();
                $table->string('number');
                $table->string('description');
                $table->timestamps();
            });
        }

        (new SampleXSeeder())->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
