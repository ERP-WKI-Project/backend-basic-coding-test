<?php

namespace Database\Factories;

use App\Models\Shift;
use App\Models\UserShift;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = null;

        if ($counter === null) {
            $max = \App\Models\User::max('employee_number');
            $counter = $max ? ((int) $max + 1) : 1;
        }

        return [
            'employee_number' => str_pad($counter++, 6, '0', STR_PAD_LEFT),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function ($user) {

            if ($user->shifts()->count() > 0) {
                return;
            }

            $dateTemplate = '%s'; // keep same template you used

            $daysOfWeek = [];

            for ($i = 1; $i <= 7; $i++) {
                $date = now()->startOfMonth()->addDays($i - 1);
                $daysOfWeek[] = $date->dayOfWeekIso;
            }

            $shifts = Shift::whereIn('day_of_week', $daysOfWeek)->get();
            $startDate = now()->startOfMonth()->day;
            $endDate = now()->endOfMonth()->day;

            $stepShift = ['Shift Pagi', 'Shift Siang', 'Shift Malam'];

            for ($dateOfMonth = $startDate; $dateOfMonth <= $endDate; $dateOfMonth++) {

                $date = now()->startOfMonth()->addDays($dateOfMonth - 1);
                $dayOfWeek = $date->dayOfWeekIso;

                $idx = ($dateOfMonth - 1) % 4;
                if ($idx === 3) {
                    continue;
                } // off day

                $shiftName = $stepShift[$idx];

                $shift = $shifts
                    ->where('day_of_week', $dayOfWeek)
                    ->where('name', $shiftName)
                    ->first();

                if (! $shift) {
                    continue;
                }

                UserShift::create([
                    'user_id' => $user->id,
                    'shift_id' => $shift->id,
                    'shift_date' => $date,
                    'machine_id' => random_int(1, 3),
                ]);
            }
        });
    }
}
