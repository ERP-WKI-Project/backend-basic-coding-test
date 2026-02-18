<?php

namespace Database\Factories;

use App\Models\Machine;
use App\Models\Shift;
use App\Models\User;
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
        $machine = Machine::factory()->create();
        return [
            'ulid' => (string) str()->ulid(),
            'user_id' => User::factory(),
            'shift_id' => Shift::factory(),
            'machine_id' => $machine->id,
            'machine_code' => $machine->code,
            'shift_date' => now()->toDateString(),
            'created_by' => User::factory(),
        ];
    }
}
