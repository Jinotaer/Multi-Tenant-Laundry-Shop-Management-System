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
        Schema::create('customer_loyalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('points')->default(0)->comment('Total loyalty points earned');
            $table->integer('stamps')->default(0)->comment('Number of stamps (1 per order)');
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze')->comment('Loyalty tier based on spending');
            $table->decimal('lifetime_spent', 12, 2)->default(0)->comment('Total amount spent lifetime');
            $table->timestamp('last_earned_at')->nullable()->comment('Last time points were earned');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_loyalties');
    }
};
