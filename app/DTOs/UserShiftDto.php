<?php

namespace App\DTOs;

readonly class UserShiftDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public int $id,
        public int $user_id,
        public int $shift_id,
        public string $shift_date,
        public ?string $machine_code,
        public ?array $shift,
        public ?array $user,
        public ?array $machine,
        public ?\DateTime $created_at,
        public ?\DateTime $updated_at,
    ) {
        //
    }

    /**
     * Create UserShiftDto from UserShift model
     */
    public static function fromModel(\App\Models\UserShift $userShift): self
    {
        return new self(
            id: $userShift->id,
            user_id: $userShift->user_id,
            shift_id: $userShift->shift_id,
            shift_date: $userShift->shift_date->format('Y-m-d'),
            machine_code: $userShift->machine_code,
            shift: $userShift->shift ? ShiftDto::fromModel($userShift->shift)->toArray() : null,
            user: $userShift->user ? [
                'id' => $userShift->user->id,
                'employee_number' => $userShift->user->employee_number,
                'name' => $userShift->user->name,
                'email' => $userShift->user->email,
            ] : null,
            machine: $userShift->machine ? [
                'id' => $userShift->machine->id,
                'machine_code' => $userShift->machine->machine_code,
                'name' => $userShift->machine->name,
                'description' => $userShift->machine->description,
                'location' => $userShift->machine->location,
                'status' => $userShift->machine->status,
            ] : null,
            created_at: $userShift->created_at,
            updated_at: $userShift->updated_at,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'shift_id' => $this->shift_id,
            'shift_date' => $this->shift_date,
            'machine_code' => $this->machine_code,
            'shift' => $this->shift,
            'user' => $this->user,
            'machine' => $this->machine,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
