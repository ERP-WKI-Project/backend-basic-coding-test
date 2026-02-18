<?php

namespace App\Services;

use App\DTOs\MachineLogDto;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\User;
use App\Models\UserShift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MachineLogService
{
    public static function addLog(MachineLogDto $dto): void
    {
        MachineLog::create([
            'user_id' => $dto->user->id,
            'machine_code' => $dto->machineCode,
            'event' => $dto->event->value,
            'log_message' => $dto->logMessage,
        ]);
    }

    public static function getMachineLogs(
        int $limit,
        ?string $userId = null,
        ?string $machineCode = null,
    ): LengthAwarePaginator {
        $query = QueryBuilder::for(MachineLog::class)
            ->with(['user', 'machine'])
            ->allowedFilters([
                AllowedFilter::exact('machine_code'),
                AllowedFilter::scope('start_after'),
                AllowedFilter::scope('start_before'),
            ]);

            if ($userId && $machineCode) {
                $query
                    ->where('user_id', $userId)
                    ->where('machine_code', $machineCode);
            }

        return $query->latest()->paginate($limit);
    }
}

