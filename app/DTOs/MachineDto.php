<?php

namespace App\DTOs;

readonly class MachineDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $machine_code,
        public ?string $description = null,
    )
    {
        //
    }

    public static function fromArray(array $data): self
    {
        return new self(
            machine_code: $data['machine_code'],
            description: $data['description'],
        );
    }

    public function toArray(): array
    {
        return [
            'machine_code' => $this->machine_code,
            'description' => $this->description,
        ];
    }
}
