<?php

namespace App\Services;

use App\DTOs\MachineLogDto;
use App\Models\MachineLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MachineLogService
{
    public static function addLog(MachineLogDto $dto): MachineLog
    {
        return MachineLog::create([
            'user_id' => $dto->user->id,
            'machine_code' => $dto->machineCode,
            'event' => $dto->event,
            'log_message' => $dto->logMessage,
        ]);
    }

    public function getAll(int $perPage = 15, ?array $filters = [], ?string $search = null): LengthAwarePaginator
    {
        return MachineLog::query()
            ->with(['user'])
            ->when($filters['machine_code'] ?? null, fn ($query, $code) => $query->where('machine_code', $code))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', $date))
            ->when($search, fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('event', 'ilike', "%{$search}%")
                    ->orWhere('log_message', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->paginate($perPage);
    }
}
