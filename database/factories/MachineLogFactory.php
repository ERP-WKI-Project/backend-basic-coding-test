<?php

namespace Database\Factories;

use App\Enums\MachineLog\EventEnum;
use App\Models\Machine;
use App\Models\User;
use App\Models\UserShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MachineLog>
 */
class MachineLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ulid' => (string) str()->ulid(),
            'user_id' => User::factory(),
            'machine_id' => Machine::factory(),
            'user_shift_id' => UserShift::factory(),
            'machine_code' => fn(array $attributes) => Machine::find($attributes['machine_id'])->code,
            'event' => fake()->randomElement(EventEnum::cases())->value,
            'log_message' => fake()->sentence(),
        ];
    }
}
