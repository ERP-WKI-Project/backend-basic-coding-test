<?php

namespace Database\Factories;

use App\Models\Machine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $machine = Machine::inRandomOrder()->first() ?? Machine::factory()->create();
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        $event = $this->faker->randomElement([
            'LOGIN_SUCCESS',
            'LOGIN_FAILED',
            'LOGOUT',
            'ACCESS_DENIED',
        ]);

        return [
            'ulid' => (string) Str::ulid(),
            'machine_code' => $machine->code ?? $this->faker->bothify('MC-###'),
            'machine_id' => $machine->id,
            'user_id' => $user->id,
            'event' => $event,
            'log_message' => $this->generateMessage($event, $user),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => now(),
        ];
    }

    private function generateMessage(string $event, $user): string
    {
        return match ($event) {
            'LOGIN_SUCCESS' => "Login successful for {$user->name}",
            'LOGIN_FAILED' => "Login failed for {$user->name}",
            'LOGOUT' => "User {$user->name} logged out",
            'ACCESS_DENIED' => "Access denied for {$user->name}",
            default => "Machine activity",
        };
    }
}
