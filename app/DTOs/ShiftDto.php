<?php

namespace App\DTOs;

class ShiftDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $dayOfWeek,
        public readonly string $startTime,
        public readonly string $endTime,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            dayOfWeek: $data['day_of_week'],
            startTime: $data['start_time'],
            endTime: $data['end_time'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'day_of_week' => $this->dayOfWeek,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
        ];
    }
}
