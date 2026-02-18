<?php

namespace Database\Factories;

use App\Enums\MachineLog\EventEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MachineLog>
 */
class MachineLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'machine_code' => 'FILLING-MACHINE-' . $this->faker->numberBetween(100, 999),
            'event' => $this->faker->randomElement(EventEnum::cases())->value,
            'log_message' => $this->faker->sentence(),
            'created_at' => now(),
        ];
    }
}
