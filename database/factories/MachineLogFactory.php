<?php

namespace Database\Factories;

use App\Models\MachineLog;
use App\Models\User;
use App\Models\Machine;
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
        $machine = Machine::factory()->create();

        return [
            'user_id' => User::factory(),
            'machine_code' => $machine->machine_code,
            'event' => fake()->randomElement([
                'login_success',
                'login_failed',
                'start_work',
                'end_work',
                'machine_error',
                'maintenance'
            ]),
            'log_message' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the log event is login_success.
     */
    public function loginSuccess(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'login_success',
            'log_message' => 'User logged in successfully',
        ]);
    }

    /**
     * Indicate that the log event is login_failed.
     */
    public function loginFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'login_failed',
            'log_message' => 'User login failed: Invalid credentials',
        ]);
    }
}

