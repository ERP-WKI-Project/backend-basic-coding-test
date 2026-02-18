<?php

namespace Database\Factories;

use App\Models\Machine;
use App\Models\User;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserShift>
 */
class UserShiftFactory extends Factory
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
            'shift_id' => Shift::factory(),
            'shift_date' => now()->toDateString(),
            'machine_code' => 'FILLING-MACHINE-001',
        ];
    }

    /**
     * Indicate that the user shift should be for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the user shift should be for a specific shift.
     */
    public function forShift(Shift $shift): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_id' => $shift->id,
        ]);
    }

    /**
     * Indicate that the user shift should be for a specific machine.
     */
    public function forMachine(Machine $machine): static
    {
        return $this->state(fn (array $attributes) => [
            'machine_code' => $machine->machine_code,
        ]);
    }

    /**
     * Indicate that the user shift should be for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the user shift should be for a specific date.
     */
    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_date' => $date,
        ]);
    }
}

