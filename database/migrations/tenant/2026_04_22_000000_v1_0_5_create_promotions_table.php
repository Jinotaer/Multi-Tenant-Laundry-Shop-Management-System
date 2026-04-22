<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('discount_percentage');
            $table->dateTime('valid_until');
            $table->timestamps();
        });

        // ------------------------------------------------------------------
        // CRITICAL DEMONSTRATION LOGIC
        // This command runs the 50 data seeds automatically during the upgrade!
        // ------------------------------------------------------------------
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\PromotionSeeder',
            '--force' => true
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
