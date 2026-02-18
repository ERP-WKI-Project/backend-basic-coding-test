<?php

namespace App\Services;

use App\DTOs\MachineLogDto;
use App\Models\MachineLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MachineLogService
{
    public static function addLog(MachineLogDto $dto): MachineLog
    {
        $log = MachineLog::create([
            'user_id' => $dto->user->id,
            'machine_code' => $dto->machineCode,
            'event' => $dto->event,
            'log_message' => $dto->logMessage,
        ]);

        return $log->load(['user', 'machine']);
    }

    public function getAll(int $perPage = 15, ?array $filters = [], ?string $search = null): LengthAwarePaginator
    {
        return MachineLog::query()
            ->with(['user', 'machine'])
            ->when($filters['machine_code'] ?? null, fn ($query, $code) => $query->where('machine_code', $code))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', $date))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('event', 'ilike', "%{$search}%")
                        ->orWhere('log_message', 'ilike', "%{$search}%")
                        ->orWhere('machine_code', 'ilike', "%{$search}%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'ilike', "%{$search}%")
                                ->orWhere('employee_number', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('machine', function ($q) use ($search) {
                            $q->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($perPage);
    }
}
