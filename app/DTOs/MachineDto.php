<?php

namespace App\DTOs;

readonly class MachineDto
{
    public function __construct(
        public string $name,
        public ?string $machine_code = null,
        public ?string $description = null,
        public ?string $pin = null,
        public ?string $status = null,
    )
    {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            pin: $data['pin'] ?? null,
            status: $data['status'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
        ];

        if ($this->machine_code !== null) {
            $data['machine_code'] = $this->machine_code;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->pin !== null) {
            $data['pin'] = $this->pin;
        }

        if ($this->status !== null) {
            $data['status'] = $this->status;
        }

        return $data;
    }
}

