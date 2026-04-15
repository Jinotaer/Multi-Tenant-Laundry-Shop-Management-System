<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Owner',
            'slug' => 'owner',
            'description' => 'Tenant owner with full access.',
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Staff',
            'slug' => 'staff',
            'description' => 'Tenant staff account.',
        ]);
    }

    public function customer(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Customer',
            'slug' => 'customer',
            'description' => 'Customer portal account.',
        ]);
    }
}
