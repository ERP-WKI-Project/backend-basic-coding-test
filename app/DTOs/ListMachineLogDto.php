<?php

namespace App\DTOs;

use Carbon\Carbon;

readonly class ListMachineLogDto
{
    public function __construct(
        public int $perPage = 10,
        public ?Carbon $startDate = null,
        public ?Carbon $endDate = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            perPage: (int) ($data['per_page'] ?? 10),
            startDate: isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
            endDate: isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
        );
    }
}
