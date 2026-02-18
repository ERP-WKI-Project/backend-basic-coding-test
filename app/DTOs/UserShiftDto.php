<?php

namespace App\DTOs;

use App\Http\Requests\BackOffice\Shift\StoreShiftRequest;
use App\Http\Requests\BackOffice\Shift\UpdateShiftRequest;

readonly class UserShiftDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $shiftUlid,
        public readonly string $machineUlid,
        public readonly string $shiftDate,
    ) {}

    public static function fromRequest(StoreShiftRequest|UpdateShiftRequest $request): self
    {
        return new self(
            userId: $request->validated('user_id'),
            shiftUlid: $request->validated('shift_id'),
            machineUlid: $request->validated('machine_id'),
            shiftDate: $request->validated('shift_date'),
        );
    }
}
