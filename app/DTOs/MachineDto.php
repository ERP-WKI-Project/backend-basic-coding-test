<?php

namespace App\DTOs;

readonly class MachineDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public int $id,
        public string $machine_code,
        public string $name,
        public ?string $description,
        public ?string $location,
        public string $status,
        public ?\DateTime $created_at,
        public ?\DateTime $updated_at,
    ) {
        //
    }

    /**
     * Create MachineDto from Machine model
     */
    public static function fromModel(\App\Models\Machine $machine): self
    {
        return new self(
            id: $machine->id,
            machine_code: $machine->machine_code,
            name: $machine->name,
            description: $machine->description,
            location: $machine->location,
            status: $machine->status,
            created_at: $machine->created_at,
            updated_at: $machine->updated_at,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'machine_code' => $this->machine_code,
            'name' => $this->name,
            'description' => $this->description,
            'location' => $this->location,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
