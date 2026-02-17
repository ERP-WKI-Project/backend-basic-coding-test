<?php

namespace App\Services;

use App\Models\MachineLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportService
{
    public function getUserMachineActivity(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return MachineLog::query()
            ->with(['user', 'machine'])
            ->when($filters['start_date'] ?? null, fn ($query, $startDate) => $query->whereDate('created_at', '>=', $startDate))
            ->when($filters['end_date'] ?? null, fn ($query, $endDate) => $query->whereDate('created_at', '<=', $endDate))
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['machine_code'] ?? null, fn ($query, $machineCode) => $query->where('machine_code', $machineCode))
            ->latest()
            ->paginate($perPage);
    }
}
