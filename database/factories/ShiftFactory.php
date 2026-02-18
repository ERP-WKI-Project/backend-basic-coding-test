<?php

namespace Database\Factories;

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
        $shifts = ['Shift Pagi', 'Shift Siang', 'Shift Malam'];

        return [
            'name' => $this->faker->randomElement($shifts),
            'day_of_week' => $this->faker->numberBetween(1, 7),
            'start_time' => '01:00:00',
            'end_time' => '06:00:00',
        ];
    }
}
