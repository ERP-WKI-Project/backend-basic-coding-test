<?php

namespace App\DTOs;

use App\Http\Requests\BackOffice\UserShift\CreateUserShiftRequest;
use App\Models\Shift;
use App\Models\User;

readonly class UserShiftDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public \App\Models\User $user,
        public \App\Models\Shift $shift,
        public string $machineCode,
        public string $shiftDate,
    )
    {
        //
    }

    public static function fromArray(array $data): self
    {
        return new self(
            user: User::findOrFail($data['user_id']),
            shift: Shift::findOrFail($data['shift_id']),
            machineCode: $data['machine_code'],
            shiftDate: $data['shift_date'],
        );
    }

    public function toArray(): array
    {
        $data = [
            'user_id' => $this->user->id,
            'shift_id' => $this->shift->id,
            'machine_code' => $this->machineCode,
            'shift_date' => $this->shiftDate,
        ];

        return $data;
    }
}
