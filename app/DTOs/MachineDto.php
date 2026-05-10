<?php

namespace App\DTOs;

readonly class MachineDto
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?string $location = null,
        public ?bool $isActive = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'] ?? null,
            name: $data['name'] ?? null,
            location: $data['location'] ?? null,
            isActive: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'name' => $this->name,
            'location' => $this->location,
            'is_active' => $this->isActive,
        ], fn ($value) => ! is_null($value));
    }
}
