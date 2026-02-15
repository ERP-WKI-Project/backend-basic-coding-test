<?php

namespace App\Services;

use App\DTOs\MachineLogDto;
use App\Models\MachineLog;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public static function getAll(int $perPage = 10): LengthAwarePaginator
    {
        return MachineLog::query()
            ->latest()
            ->paginate($perPage);
    }
}
