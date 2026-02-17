<?php

namespace App\DTOs;

class MachineDto
{
    public function __construct(
        public readonly string $machineCode,
        public readonly string $name,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            machineCode: $data['machine_code'],
            name: $data['name'],
            description: $data['description'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'machine_code' => $this->machineCode,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
