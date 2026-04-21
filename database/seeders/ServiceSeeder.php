<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            // South Region Services
            [
                'name' => 'Wash & Fold (South)',
                'description' => 'Standard washing and folding service for South region',
                'price_type' => 'per_kilo',
                'price' => 45.00,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Wash & Iron (South)',
                'description' => 'Washing with ironing service for South region',
                'price_type' => 'per_kilo',
                'price' => 65.00,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Dry Cleaning (South)',
                'description' => 'Professional dry cleaning for South region',
                'price_type' => 'per_kilo',
                'price' => 150.00,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Iron Only (South)',
                'description' => 'Ironing service only for South region',
                'price_type' => 'per_kilo',
                'price' => 35.00,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Express Service (South)',
                'description' => 'Same-day express service for South region',
                'price_type' => 'per_kilo',
                'price' => 85.00,
                'is_active' => true,
                'sort_order' => 5,
            ],

            // North Region Services
            [
                'name' => 'Wash & Fold (North)',
                'description' => 'Standard washing and folding service for North region',
                'price_type' => 'per_kilo',
                'price' => 50.00,
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Wash & Iron (North)',
                'description' => 'Washing with ironing service for North region',
                'price_type' => 'per_kilo',
                'price' => 70.00,
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Dry Cleaning (North)',
                'description' => 'Professional dry cleaning for North region',
                'price_type' => 'per_kilo',
                'price' => 160.00,
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Iron Only (North)',
                'description' => 'Ironing service only for North region',
                'price_type' => 'per_kilo',
                'price' => 40.00,
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'Express Service (North)',
                'description' => 'Same-day express service for North region',
                'price_type' => 'per_kilo',
                'price' => 90.00,
                'is_active' => true,
                'sort_order' => 10,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
