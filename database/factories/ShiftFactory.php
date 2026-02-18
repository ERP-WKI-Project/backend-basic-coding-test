<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'ulid' => Str::ulid(),
            'name' => 'Shift Pagi',
            'day_of_week' => 1,
            'start_time' => '07:00:00',
            'end_time' => '15:00:00',
        ];
    }
}
