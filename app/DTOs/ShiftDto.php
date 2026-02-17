<?php

namespace App\DTOs;

readonly class ShiftDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public int $id,
        public string $ulid,
        public string $name,
        public int $day_of_week,
        public string $start_time,
        public string $end_time,
        public ?\DateTime $created_at,
        public ?\DateTime $updated_at,
    ) {
        //
    }

    /**
     * Create ShiftDto from Shift model
     */
    public static function fromModel(\App\Models\Shift $shift): self
    {
        return new self(
            id: $shift->id,
            ulid: $shift->ulid,
            name: $shift->name,
            day_of_week: $shift->day_of_week,
            start_time: $shift->start_time,
            end_time: $shift->end_time,
            created_at: $shift->created_at,
            updated_at: $shift->updated_at,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'name' => $this->name,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
