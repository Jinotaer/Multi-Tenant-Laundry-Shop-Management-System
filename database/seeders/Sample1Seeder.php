<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sample1;

class Sample1Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Sample1::query()->count() === 0) {
            Sample1::factory(50)->create();
        }
    }
}
