<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\SampledemoSeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sampledemo')) {
            Schema::create('sampledemo', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('age')->unsigned();
                $table->timestamps();
            });
        }

        (new SampledemoSeeder())->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
