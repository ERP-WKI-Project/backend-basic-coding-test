<?php

namespace App\DTOs;

readonly class UserShiftDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $user_id,
        public string $shift_id,
        public ?string $machine_id = null,
        public string $shift_date,
    )
    {}

    public static function fromArray(array $data): self
    {
        return new self(
            user_id: $data['user_id'],
            shift_id: $data['shift_id'],
            machine_id: $data['machine_id'] ?? null,
            shift_date: $data['shift_date'],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->user_id,
            'shift_id' => $this->shift_id,
            'machine_id' => $this->machine_id,
            'shift_date' => $this->shift_date,
        ], fn ($v) => ! is_null($v));
    }}
