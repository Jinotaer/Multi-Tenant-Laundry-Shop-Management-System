<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Module::query()->count() === 0) {
            Module::factory(50)
                ->withSequentialPlanIds()
                ->create();
        }
    }
}
