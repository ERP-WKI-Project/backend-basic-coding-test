<?php

namespace Database\Factories;

use App\Models\Machine;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserShiftFactory extends Factory
{
    protected $model = UserShift::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shift_id' => Shift::factory(),
            'machine_id' => Machine::factory(),
            'shift_date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function forMachine(Machine $machine): static
    {
        return $this->state(fn (array $attributes) => [
            'machine_id' => $machine->id,
        ]);
    }

    public function forShift(Shift $shift): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_id' => $shift->id,
        ]);
    }

    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_date' => $date,
        ]);
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_date' => now()->format('Y-m-d'),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_date' => $this->faker->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ]);
    }

    public function future(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_date' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
        ]);
    }
}
