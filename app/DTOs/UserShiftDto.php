<?php

namespace App\DTOs;

class UserShiftDto
{
    public function __construct(
        public readonly int $userId,
        public readonly int $shiftId,
        public readonly string $shiftDate,
        public readonly string $machineCode,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            shiftId: $data['shift_id'],
            shiftDate: $data['shift_date'],
            machineCode: $data['machine_code'],
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'shift_id' => $this->shiftId,
            'shift_date' => $this->shiftDate,
            'machine_code' => $this->machineCode,
            'notes' => $this->notes,
        ];
    }
}
