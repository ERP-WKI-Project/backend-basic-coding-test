<?php

namespace Database\Factories;

use App\Enums\MachineStatusEnum;
use App\Models\Machine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MachineFactory extends Factory
{
    protected $model = Machine::class;

    private static array $machineTypes = [
        'FILLING' => 'Filling Machine',
        'PACKAGING' => 'Packaging Machine',
        'LABELING' => 'Labeling Machine',
        'CONVEYOR' => 'Conveyor Belt',
        'MIXING' => 'Mixing Machine',
        'CAPPING' => 'Capping Machine',
        'SEALING' => 'Sealing Machine',
        'WRAPPING' => 'Wrapping Machine',
        'PALLETIZING' => 'Palletizing Robot',
        'INSPECTION' => 'Quality Inspection',
    ];

    private static array $locations = [
        'Plant A - Floor 1',
        'Plant A - Floor 2',
        'Plant B - Floor 1',
        'Plant B - Floor 2',
        'Warehouse Section A',
        'Warehouse Section B',
        'Production Line 1',
        'Production Line 2',
        'Production Line 3',
        'QC Laboratory',
    ];

    public function definition(): array
    {
        $type = $this->faker->randomElement(array_keys(self::$machineTypes));
        $uniqueId = $this->faker->unique()->numerify('###');

        return [
            'ulid' => (string) Str::ulid(),
            'code' => "{$type}-MACHINE-{$uniqueId}",
            'name' => self::$machineTypes[$type]." Line {$uniqueId}",
            'location' => $this->faker->randomElement(self::$locations),
            'status' => $this->faker->randomElement(MachineStatusEnum::cases()),
            'description' => $this->faker->sentence(10),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MachineStatusEnum::ACTIVE,
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MachineStatusEnum::MAINTENANCE,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MachineStatusEnum::INACTIVE,
        ]);
    }

    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $code,
        ]);
    }
}
