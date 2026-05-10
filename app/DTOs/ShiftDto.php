<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class ShiftDto
{
    public function __construct(
        public ?string $ulid,
        public string $name,
        public int $dayOfWeek,
        public string $startTime,
        public string $endTime,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            ulid: null,
            name: $request->input('name'),
            dayOfWeek: (int) $request->input('day_of_week'),
            startTime: $request->input('start_time'),
            endTime: $request->input('end_time'),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            ulid: $data['ulid'] ?? null,
            name: $data['name'] ?? '',
            dayOfWeek: (int) ($data['day_of_week'] ?? 0),
            startTime: $data['start_time'] ?? '',
            endTime: $data['end_time'] ?? '',
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
