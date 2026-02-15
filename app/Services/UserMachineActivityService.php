<?php

namespace App\Services;

use App\Models\MachineLog;
use Illuminate\Pagination\LengthAwarePaginator;

class UserMachineActivityService
{
    public function getByRangeDate(string $startDate, string $endDate, int $perPage = 10): LengthAwarePaginator
    {
        return MachineLog::query()
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy('created_at')
            ->paginate($perPage);
    }
}
