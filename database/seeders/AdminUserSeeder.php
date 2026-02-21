<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::firstOrCreate(
            ['email' => '2301106754@student.buksu.edu.ph'],
            [
                'name' => 'Spade Kun',
                'password' => Hash::make('password123'),
            ]
        );

        $this->command->info('Admin user created successfully. Email: 2301106754@student.buksu.edu.ph, Password: password123');
    }
}
