<?php

namespace App\DTOs;

final readonly class UpdateShiftDto
{

    public function __construct(
        public string $name,
        public string $dayOfWeek,
        public string $startTime,
        public string $endTime,
    ) {
    }

    public static function fromRequest(\App\Http\Requests\BackOffice\UpdateShiftRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            dayOfWeek: $request->input('day_of_week'),
            startTime: $request->input('start_time'),
            endTime: $request->input('end_time'),
        );
    }
}
