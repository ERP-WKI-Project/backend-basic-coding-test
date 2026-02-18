<?php

namespace App\Services;

use App\DTOs\MachineLogDto;
use App\Models\MachineLog;
use Illuminate\Support\Str;

class MachineLogService
{
    public static function addLog(MachineLogDto $dto): void
    {
        MachineLog::create([
            'ulid' => (string) Str::ulid(),
            'machine_id' => $dto->machineId,
            'user_id' => $dto->user->id,
            'event' => $dto->event->value,
            'log_message' => $dto->logMessage,
            'metadata' => $dto->metadata,
        ]);
    }
}
