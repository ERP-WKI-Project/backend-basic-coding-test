<?php

namespace App\DTOs;

use App\Models\Shift;
use App\Models\User;

readonly class UserShiftDto
{
    public function __construct(
        public int $user_id,
        public int $shift_id,
        public string $shift_date,
        public ?string $machine_code = null,
    )
    {
    }

    public static function fromRequest(array $data): self
    {
        // Resolve employee_number to user_id
        $user = User::where('employee_number', $data['employee_number'])->firstOrFail();

        // Resolve shift_ulid to shift_id
        $shift = Shift::where('ulid', $data['shift_ulid'])->firstOrFail();

        return new self(
            user_id: $user->id,
            shift_id: $shift->id,
            shift_date: $data['shift_date'],
            machine_code: $data['machine_code'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'shift_id' => $this->shift_id,
            'shift_date' => $this->shift_date,
            'machine_code' => $this->machine_code,
        ];
    }
}

