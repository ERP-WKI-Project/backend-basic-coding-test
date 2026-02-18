<?php

namespace App\DTOs\UserShift;

use App\Http\Requests\BackOffice\UpdateUserShiftRequest;

readonly class UpdateUserShiftDto
{
    public function __construct(
        public ?int $userId = null,
        public ?int $shiftId = null,
        public ?string $shiftDate = null,
        public ?string $machineCode = null,
    ) {}

    public static function fromRequest(UpdateUserShiftRequest $request): self
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
        $data = array_filter([
            'user_id' => $this->userId,
            'shift_id' => $this->shiftId,
            'shift_date' => $this->shiftDate,
            'machine_code' => $this->machineCode,
        ], fn ($value) => ! is_null($value));

        return $data;
    }
}
