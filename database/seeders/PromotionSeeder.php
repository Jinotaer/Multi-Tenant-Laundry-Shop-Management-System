<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        // Seed exactly 50 dummy records for the demonstration
        Promotion::factory()->count(50)->create();
    }
}
