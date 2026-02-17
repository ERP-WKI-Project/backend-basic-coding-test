<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        $startHour = fake()->numberBetween(6, 14);
        $endHour = $startHour + fake()->numberBetween(4, 10);

        return [
            'name' => fake()->randomElement([
                'Morning Shift',
                'Afternoon Shift',
                'Evening Shift',
                'Night Shift',
                'Day Shift',
            ]) . ' - ' . fake()->word(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', min($endHour, 23)),
        ];
    }

    public function monday(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => 1,
            'name' => 'Monday ' . $attributes['name'],
        ]);
    }

    public function morning(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Morning Shift',
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);
    }

    public function afternoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Afternoon Shift',
            'start_time' => '14:00',
            'end_time' => '22:00',
        ]);
    }

    public function night(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Night Shift',
            'start_time' => '22:00',
            'end_time' => '06:00',
        ]);
    }
}
