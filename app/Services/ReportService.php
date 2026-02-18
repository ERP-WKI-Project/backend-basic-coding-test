<?php

namespace App\Services;

use App\DTOs\ListMachineLogDto;
use App\DTOs\MachineLogDto;
use App\Models\MachineLog;

class ReportService
{
    public function list(int $perPage = 10, ListMachineLogDto $dto)
    {
        $query = MachineLog::with('user')->latest();

        if ($dto->startDate) {
            $query->whereDate('created_at', '>=', $dto->startDate);
        }

        if ($dto->endDate) {
            $query->whereDate('created_at', '<=', $dto->endDate);
        }

        return $query->paginate($perPage);
    }
}
