<?php

namespace App\Services;

use App\DTOs\MachineLogDto;
use App\Models\MachineLog;

class MachineLogService
{
    /**
     * Get a paginated list of machine logs
     *
     * @param  int  $perPage  Number of items per page (default 10)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(int $perPage = 10)
    {
        // Retrieve all MachineLog records ordered by newest first
        // 'latest()' orders by the 'created_at' column in descending order
        // 'paginate($perPage)' returns a paginated result
        return MachineLog::latest()->paginate($perPage);
    }

    /**
     * Add a new machine log entry using data from a DTO
     *
     * @param  MachineLogDto  $dto  Data Transfer Object containing validated log data
     * @return MachineLog The newly created MachineLog model
     */
    public static function addLog(MachineLogDto $dto): MachineLog
    {
        // Create a new MachineLog record in the database
        // Map the DTO properties to the database columns
        return MachineLog::create([
            'user_id' => $dto->user->id,           // ID of the user performing the action
            'machine_code' => $dto->machineCode,   // Machine code related to the log
            'event' => $dto->event->value,         // Event type, stored as the enum value
            'log_message' => $dto->logMessage,     // Optional message describing the log
        ]);
    }
}
