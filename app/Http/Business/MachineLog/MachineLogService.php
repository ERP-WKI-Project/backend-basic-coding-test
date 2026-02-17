<?php

namespace App\Http\Business\MachineLog;

use App\DTOs\MachineLogDto;
use App\Enums\MachineLog\EventEnum;
use App\Models\MachineLog;
use App\Models\User;
use App\Models\Machine;

class MachineLogService
{
    /**
     * Create a new machine log entry
     */
    public function createLog(array $data): ?MachineLogDto
    {
        // Validate user exists
        $user = User::getByID($data['user_id']);
        if (!$user) {
            return null;
        }

        // Validate machine exists
        $machine = Machine::getByMachineCode($data['machine_code']);
        if (!$machine) {
            return null;
        }

        // Create the log entry
        $machineLog = MachineLog::createLog($data);

        // Convert event string to EventEnum
        $eventEnum = EventEnum::tryFrom($data['event']) ?? EventEnum::from($data['event']);

        return new MachineLogDto(
            user: $user,
            machineCode: $data['machine_code'],
            event: $eventEnum,
            logMessage: $data['log_message']
        );
    }

    /**
     * Get machine log detail by id
     */
    public function getLogById(int $id): ?MachineLogDto
    {
        $machineLog = MachineLog::getWithRelationsById($id);

        if (!$machineLog) {
            return null;
        }

        $eventEnum = EventEnum::tryFrom($machineLog->event) ?? EventEnum::from($machineLog->event);

        return new MachineLogDto(
            user: $machineLog->user,
            machineCode: $machineLog->machine_code,
            event: $eventEnum,
            logMessage: $machineLog->log_message
        );
    }

    /**
     * Get machine logs with pagination and optional filters
     */
    public function getLogsFormatted(
        int $perPage = 15,
        int $page = 1,
        ?string $machineCode = null,
        ?int $userId = null,
        ?string $event = null
    ): ?array {
        // Validate machine exists if machineCode is provided
        if ($machineCode !== null) {
            $machine = Machine::getByMachineCode($machineCode);
            if (!$machine) {
                return null;
            }
        }

        // Validate user exists if userId is provided
        if ($userId !== null) {
            $user = User::find($userId);
            if (!$user) {
                return null;
            }
        }

        $paginator = MachineLog::getWithRelationsPaginated($perPage, $page, $machineCode, $userId, $event);

        return [
            'machine_logs' => $paginator->getCollection()->map(function ($log) {
                $eventEnum = EventEnum::tryFrom($log->event) ?? EventEnum::from($log->event);
                return new MachineLogDto(
                    user: $log->user,
                    machineCode: $log->machine_code,
                    event: $eventEnum,
                    logMessage: $log->log_message
                );
            }),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
    }

    /**
     * Convert DTO to array for JSON response
     */
    public function dtoToArray(MachineLogDto $dto): array
    {
        return [
            'user' => [
                'id' => $dto->user->id,
                'name' => $dto->user->name,
                'email' => $dto->user->email,
            ],
            'machine_code' => $dto->machineCode,
            'event' => $dto->event->value,
            'log_message' => $dto->logMessage,
        ];
    }
}
