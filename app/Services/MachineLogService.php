<?php

namespace App\Services;

use App\DTOs\MachineLogDto;
use App\Models\MachineLog;

class MachineLogService
{
    public function list(int $perPage = 10)
    {
        return MachineLog::latest()->paginate($perPage);
    }

    public static function addLog(MachineLogDto $dto): MachineLog
    {
        return MachineLog::create([
            'user_id' => $dto->user->id,
            'machine_code' => $dto->machineCode,
            'event' => $dto->event->value,
            'log_message' => $dto->logMessage,
        ]);
    }
}
