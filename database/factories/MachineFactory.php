<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Machine>
 */
class MachineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'machine_code' => strtoupper(fake()->unique()->bothify('??-####')),
            'name' => fake()->words(3, true) . ' Machine',
            'description' => fake()->optional(0.7)->sentence(),
            'is_active' => fake()->boolean(90), // 90% active
        ];
    }
}
