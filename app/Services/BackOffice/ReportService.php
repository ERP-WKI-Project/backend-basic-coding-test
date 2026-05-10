<?php

namespace App\Services\BackOffice;

use App\Models\UserShift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportService
{
    public function getUserMachineActivity(
        ?string $startDate,
        ?string $endDate,
        ?string $userId = null,
        ?string $machineCode = null,
        int $limit = 15,
        ?string $search = null
    ): LengthAwarePaginator {
        $query = UserShift::query()
            ->with(['user', 'shift', 'machineLogs', 'machineLogs.machine'])
            ->when($startDate, fn ($q) => $q->whereDate('shift_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('shift_date', '<=', $endDate))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($machineCode, fn ($q) => $q->whereHas('machineLogs', fn ($mq) => $mq->where('machine_code', $machineCode)))
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'ilike', "%{$search}%"))
                    ->orWhereHas('user', fn ($uq) => $uq->where('employee_number', 'ilike', "%{$search}%"));
            })
            ->orderBy('shift_date', 'desc');

        $paginatedUserShifts = $query->paginate($limit);

        return $this->formatPaginatedResponse($paginatedUserShifts);
    }

    private function formatPaginatedResponse(LengthAwarePaginator $paginatedUserShifts): LengthAwarePaginator
    {
        $grouped = $paginatedUserShifts->getCollection()->groupBy(fn ($us) => $us->user_id)->map(function (Collection $shifts) {
            $first = $shifts->first();

            return [
                'user_id' => $first->user_id,
                'user_name' => $first->user->name,
                'employee_number' => $first->user->employee_number,
                'shifts' => $shifts->groupBy(fn ($us) => $us->shift_date->toDateString())->map(function (Collection $dayShifts) {
                    $firstShift = $dayShifts->first();

                    return [
                        'shift_date' => $firstShift->shift_date->toDateString(),
                        'shift_name' => $firstShift->shift?->name,
                        'shift_start_time' => $firstShift->shift?->start_time,
                        'shift_end_time' => $firstShift->shift?->end_time,
                        'assigned_machine' => $firstShift->machine_code,
                        'activities' => $this->formatActivities($dayShifts),
                    ];
                })->values()->toArray(),
            ];
        })->values();

        return $paginatedUserShifts->setCollection($grouped);
    }

    private function formatActivities(Collection $dayShifts): array
    {
        $activities = $dayShifts->flatMap(fn ($us) => $us->machineLogs)->filter();

        if ($activities->isEmpty()) {
            return [];
        }

        return $activities->map(fn ($log) => [
            'machine_code' => $log->machine_code,
            'machine_name' => $log->machine?->name,
            'event' => $log->event,
            'log_message' => $log->log_message,
            'timestamp' => $log->created_at?->toIso8601String(),
        ])->sortBy('timestamp')->values()->toArray();
    }
}
