<?php

namespace Database\Factories;

use App\Enums\MachineLog\EventEnum;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MachineLogFactory extends Factory
{
    protected $model = MachineLog::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'machine_id' => Machine::factory(),
            'user_id' => User::factory(),
            'event' => $this->faker->randomElement(EventEnum::cases())->value,
            'log_message' => $this->faker->sentence(10),
            'metadata' => null,
        ];
    }

    public function forMachine(Machine $machine): static
    {
        return $this->state(fn (array $attributes) => [
            'machine_id' => $machine->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function withEvent(EventEnum $event): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => $event->value,
        ]);
    }

    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata,
        ]);
    }
}
