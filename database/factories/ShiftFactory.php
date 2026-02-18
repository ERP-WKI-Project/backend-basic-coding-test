<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Shift Pagi',
            'day_of_week' => fake()->numberBetween(1, 7),
            'start_time' => '07:00:00',
            'end_time' => '15:00:00',
        ];
    }

    /**
     * Indicate that the shift is morning shift (07:00 - 15:00)
     */
    public function morning(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Pagi',
            'start_time' => '07:00:00',
            'end_time' => '15:00:00',
        ]);
    }

    /**
     * Indicate that the shift is afternoon shift (15:00 - 23:00)
     */
    public function afternoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Sore',
            'start_time' => '15:00:00',
            'end_time' => '23:00:00',
        ]);
    }

    /**
     * Indicate that the shift is night shift (23:00 - 07:00)
     */
    public function night(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Malam',
            'start_time' => '23:00:00',
            'end_time' => '07:00:00',
        ]);
    }

    /**
     * Indicate a specific day of week (1 = Monday, 7 = Sunday)
     */
    public function forDayOfWeek(int $dayOfWeek): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $dayOfWeek,
        ]);
    }
}

