<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price' => fake()->randomElement([0, 500, 1500, 2500]),
            'billing_cycle' => 'monthly',
            'staff_limit' => fake()->randomElement([1, 5, 0]),
            'customer_limit' => fake()->randomElement([50, 200, null]),
            'order_limit' => fake()->randomElement([100, 500, null]),
            'features' => [],
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
        ];
    }

    /**
     * Mark the plan as the default/free plan.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 0,
            'staff_limit' => 1,
            'customer_limit' => 50,
            'order_limit' => 100,
            'is_default' => true,
        ]);
    }

    /**
     * Mark the plan as a premium plan.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Premium',
            'slug' => 'premium',
            'price' => 2500,
            'staff_limit' => 0,
            'customer_limit' => null,
            'order_limit' => null,
            'is_default' => false,
        ]);
    }

    /**
     * Mark the plan as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
