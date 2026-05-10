<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class UserShiftDto
{
    public function __construct(
        public int $userId,
        public int $shiftId,
        public string $shiftDate,
        public ?string $machineCode = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (int) $request->input('user_id'),
            shiftId: (int) $request->input('shift_id'),
            shiftDate: $request->input('shift_date'),
            machineCode: $request->input('machine_code'),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            shiftId: (int) $data['shift_id'],
            shiftDate: $data['shift_date'],
            machineCode: $data['machine_code'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'shift_id' => $this->shiftId,
            'shift_date' => $this->shiftDate,
            'machine_code' => $this->machineCode,
        ];
    }
}
