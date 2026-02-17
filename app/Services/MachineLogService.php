<?php

namespace App\Services;

use App\DTOs\MachineLogDto;
use App\Enums\MachineLog\EventEnum;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\User;
use App\Models\UserShift;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MachineLogService
{
    protected MachineLog $model;
    protected UserShift $userShiftModel;
    protected Machine $machineModel;

    public function __construct(MachineLog $model, UserShift $userShiftModel, Machine $machineModel)
    {
        $this->model = $model;
        $this->userShiftModel = $userShiftModel;
        $this->machineModel = $machineModel;
    }

    /**
     * Get paginated machine logs with filters.
     */
    public function getPaginatedLogs(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['user', 'machine'])
            ->latest()
            ->when($filters['machine_id'] ?? null, function ($query, $machineUlid) {
                $query->whereHas('machine', fn($q) => $q->where('ulid', $machineUlid));
            })
            ->when($filters['event'] ?? null, function ($query, $event) {
                $query->where('event', $event);
            })
            ->when($filters['date'] ?? null, function ($query, $date) {
                $query->whereDate('created_at', $date);
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('log_message', 'ilike', "%{$search}%")
                        ->orWhere('machine_code', 'ilike', "%{$search}%")
                        ->orWhereHas('user', fn($u) => $u->where('name', 'ilike', "%{$search}%"));
                });
            })
            ->paginate($perPage);
    }

    public function validateActiveSession(User $user, Machine $machine): void
    {
        $lastLog = $this->model
            ->where('user_id', $user->id)
            ->where('machine_id', $machine->id)
            ->whereDate('created_at', now()->toDateString())
            ->latest()
            ->first();

        if (!$lastLog || $lastLog->event !== EventEnum::LOGIN_SUCCESS->value) {
            throw new \Exception("Unauthorized: You must login to this machine before recording activities.");
        }
    }

    public function validateShift(User $user, Machine $machine): void
    {
        $hasActiveShift = $this->userShiftModel
            ->where('user_id', $user->id)
            ->where('machine_id', $machine->id)
            ->whereDate('shift_date', now()->toDateString())
            ->exists();

        if (!$hasActiveShift) {
            throw new Exception("Unauthorized: You are not assigned to this machine for today's shift.");
        }
    }

    public function addLog(MachineLogDto $dto): void
    {
        DB::transaction(function () use ($dto) {
            $this->model->create([
                'user_id'      => $dto->user->id,
                'machine_id'   => $dto->machineId,
                'machine_code' => $dto->machineCode,
                'event'        => $dto->event->value,
                'log_message'  => $dto->logMessage,
            ]);
        });
    }
}
