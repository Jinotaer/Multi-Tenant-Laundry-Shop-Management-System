<?php

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Module>
 */
class ModuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Module>
     */
    protected $model = Module::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(),
            'plan_id' => 1,
        ];
    }

    /**
     * Apply sequential plan IDs starting from the provided value.
     */
    public function withSequentialPlanIds(int $start = 1): static
    {
        return $this->state(new Sequence(
            fn (Sequence $sequence): array => ['plan_id' => $start + $sequence->index]
        ));
    }
}
