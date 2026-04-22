<?php

namespace Database\Factories;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Sale',
            'discount_percentage' => $this->faker->numberBetween(5, 50),
            'valid_until' => $this->faker->dateTimeBetween('+1 week', '+6 months'),
        ];
    }
}
