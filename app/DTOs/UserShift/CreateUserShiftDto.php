<?php

namespace App\DTOs\UserShift;

use App\Http\Requests\BackOffice\StoreUserShiftRequest;

readonly class CreateUserShiftDto
{
    public function __construct(
        public int $userId,
        public int $shiftId,
        public string $shiftDate,
        public ?string $machineCode,
    ) {}

    public static function fromRequest(StoreUserShiftRequest $request): self
    {
        return new self(
            userId: $request->validated('user_id'),
            shiftId: $request->validated('shift_id'),
            shiftDate: $request->validated('shift_date'),
            machineCode: $request->validated('machine_code'),
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
