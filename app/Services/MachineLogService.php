<?php

namespace App\Services;

use App\DTOs\MachineLogDto;
use App\Models\MachineLog;
use App\Models\User;
use App\Models\UserShift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
        UserShift $userShift,
        int $limit = 15,
        array $filters = []
    ): LengthAwarePaginator {
        $query = MachineLog::query()
            ->with(['user', 'machine'])
            ->where('user_id', $userShift->user_id)
            ->where('machine_code', $userShift->machine_code);

        // Filter by event type
        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($limit);
    }
}

