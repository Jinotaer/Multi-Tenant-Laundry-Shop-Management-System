<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sampledemo;

class SampledemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Sampledemo::query()->count() === 0) {
            Sampledemo::factory(50)->create();
        }
    }
}
