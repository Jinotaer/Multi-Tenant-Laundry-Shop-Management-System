<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sample1;

class Sample1Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        if (Sample1::count() == 0) {
            Sample1::factory(50)->create();
        }
    }
}
