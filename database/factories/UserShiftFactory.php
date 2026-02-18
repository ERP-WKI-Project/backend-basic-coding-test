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
            'shift_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'machine_code' => Machine::factory()->create()->machine_code,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_date' => now()->toDateString(),
        ]);
    }

    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_date' => $date,
        ]);
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    public function forShift(int $shiftId): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_id' => $shiftId,
        ]);
    }

    public function forMachine(string $machineCode): static
    {
        return $this->state(fn (array $attributes) => [
            'machine_code' => $machineCode,
        ]);
    }
}
