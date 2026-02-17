<?php

namespace App\Services;

use App\DTOs\MachineLogDto;
use App\Models\MachineLog;
use Illuminate\Database\Eloquent\Collection;

class MachineLogService
{
    /**
     * Get logs for a specific machine
     */
    public function getLogsByMachine(string $machineCode, ?int $userId = null): Collection
    {
        $query = MachineLog::where('machine_code', $machineCode)
            ->with('user')
            ->orderBy('created_at', 'desc');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->get();
    }

    /**
     * Get logs for a specific user
     */
    public function getLogsByUser(int $userId, ?string $machineCode = null): Collection
    {
        $query = MachineLog::where('user_id', $userId)
            ->with('machine')
            ->orderBy('created_at', 'desc');

        if ($machineCode !== null) {
            $query->where('machine_code', $machineCode);
        }

        return $query->get();
    }

    /**
     * Create a new log entry
     */
    public function createLog(MachineLogDto $dto): MachineLog
    {
        return MachineLog::create($dto->toArray());
    }

    /**
     * Legacy method - kept for backward compatibility
     */
    public static function addLog(MachineLogDto $dto): void
    {
        MachineLog::create($dto->toArray());
    }
}
