<?php

namespace Database\Factories;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    private static array $shiftNames = ['Shift Pagi', 'Shift Siang', 'Shift Malam'];

    private static array $shiftTimes = [
        ['07:00:00', '15:00:00'],
        ['15:00:00', '23:00:00'],
        ['23:00:00', '07:00:00'],
    ];

    public function definition(): array
    {
        $dayOfWeek = $this->faker->numberBetween(1, 7);
        $shiftIndex = $this->faker->numberBetween(0, 2);
        $dateTemplate = sprintf('1990-01-%02d', $dayOfWeek);

        return [
            'ulid' => (string) Str::ulid(Carbon::parse($dateTemplate)),
            'day_of_week' => $dayOfWeek,
            'name' => self::$shiftNames[$shiftIndex],
            'start_time' => self::$shiftTimes[$shiftIndex][0],
            'end_time' => self::$shiftTimes[$shiftIndex][1],
        ];
    }

    public function forDay(int $dayOfWeek): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $dayOfWeek,
        ]);
    }

    public function morning(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Pagi',
            'start_time' => '07:00:00',
            'end_time' => '15:00:00',
        ]);
    }

    public function afternoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Siang',
            'start_time' => '15:00:00',
            'end_time' => '23:00:00',
        ]);
    }

    public function night(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Malam',
            'start_time' => '23:00:00',
            'end_time' => '07:00:00',
        ]);
    }
}
