<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert old status values to new ones
        DB::table('orders')->where('status', 'pending')->update(['status' => 'received']);
        DB::table('orders')->where('status', 'delivered')->update(['status' => 'claimed']);

        // Update the enum column to support all new statuses
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 20)->default('received')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('orders')->where('status', 'received')->update(['status' => 'pending']);
        DB::table('orders')->where('status', 'claimed')->update(['status' => 'delivered']);
        DB::table('orders')->where('status', 'in_progress')->update(['status' => 'washing']);
        DB::table('orders')->where('status', 'folding')->update(['status' => 'drying']);

        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }
};
