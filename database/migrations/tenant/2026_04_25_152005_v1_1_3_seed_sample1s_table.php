<?php

use Database\Seeders\Sample1Seeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new Sample1Seeder())->run();
    }

    public function down(): void
    {
        \App\Models\Sample1::query()->truncate();
    }
};
