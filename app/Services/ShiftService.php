<?php

namespace App\Services;

use App\DTOs\UserShiftDto;
use App\Models\Machine;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    protected Shift $model;
    protected UserShift $userShiftModel;
    protected Machine $machineModel;
    protected User $userModel;

    /**
     * Create a new class instance.
     */
    public function __construct(Shift $model, UserShift $userShiftModel, Machine $machineModel, User $userModel)
    {
        $this->model = $model;
        $this->userShiftModel = $userShiftModel;
        $this->machineModel = $machineModel;
        $this->userModel = $userModel;
    }

    public function getPaginatedUserShifts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->userShiftModel
            ->with(['user', 'shift', 'machine', 'createdBy'])
            ->latest('shift_date')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'ilike', "%{$search}%")
                            ->orWhere('employee_number', 'ilike', "%{$search}%");
                    })->orWhereHas('machine', function ($machineQuery) use ($search) {
                        $machineQuery->where('name', 'ilike', "%{$search}%")
                            ->orWhere('code', 'ilike', "%{$search}%");
                    });
                });
            })
            ->when($filters['date'] ?? null, function ($query, $date) {
                $query->whereDate('shift_date', $date);
            })
            ->when($filters['machine_id'] ?? null, function ($query, $machineUlid) {
                $query->whereHas('machine', function ($q) use ($machineUlid) {
                    $q->where('ulid', $machineUlid);
                });
            })
            ->paginate($perPage);
    }

    public function createShift(UserShiftDto $dto): UserShift
    {
        return DB::transaction(function () use ($dto) {
            $user = $this->userModel->findByUlid($dto->userUlid);
            $shift = $this->model->findByUlid($dto->shiftUlid);
            $machine = $this->machineModel->findByUlid($dto->machineUlid);

            $this->validateDayMatching($shift, $dto->shiftDate);
            $this->validateUserAvailability($user->id, $dto->shiftDate);
            $this->validateMachineAvailability($machine->id, $dto->shiftDate, $shift->id);

            return $this->userShiftModel->create([
                'user_id'    => $user->id,
                'shift_id'   => $shift->id,
                'machine_id' => $machine->id,
                'machine_code' => $machine->code,
                'shift_date' => $dto->shiftDate,
                'created_by' => auth()->id(),
            ]);
        });
    }

    public function updateShift(UserShift $userShift, UserShiftDto $dto): UserShift
    {
        return DB::transaction(function () use ($userShift, $dto) {
            $user = $this->userModel->findByUlid($dto->userUlid);
            $shift = $this->model->findByUlid($dto->shiftUlid);
            $machine = $this->machineModel->findByUlid($dto->machineUlid);

            $this->validateDayMatching($shift, $dto->shiftDate);
            $this->validateUserAvailability($user->id, $dto->shiftDate, $userShift->id);
            $this->validateMachineAvailability($machine->id, $dto->shiftDate, $shift->id, $userShift->id);

            $userShift->update([
                'user_id'    => $user->id,
                'shift_id'   => $shift->id,
                'machine_id' => $machine->id,
                'shift_date' => $dto->shiftDate,
            ]);

            return $userShift->load(['user', 'shift', 'machine']);
        });
    }

    private function validateDayMatching(Shift $shift, string $date): void
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;

        if ($shift->day_of_week != $dayOfWeek) {
            throw new Exception("The selected shift does not match the day of the week for this date.");
        }
    }

    private function validateUserAvailability(int $userId, string $date, ?int $ignoreId = null): void
    {
        $exists = $this->userShiftModel->where('user_id', $userId)
            ->where('shift_date', $date)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw new Exception("Employee already has a shift on {$date}.");
        }
    }

    private function validateMachineAvailability(int $machineId, string $date, int $shiftId, ?int $ignoreId = null): void
    {
        $busy = $this->userShiftModel->where('machine_id', $machineId)
            ->where('shift_date', $date)
            ->where('shift_id', $shiftId)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($busy) {
            throw new Exception("This machine is already assigned to another employee for this shift.");
        }
    }

    public function deleteShift(UserShift $userShift): bool
    {
        return DB::transaction(function () use ($userShift) {
            $this->validateDeletionTime($userShift);

            if ($userShift->created_by !== auth()->id() && !auth()->user()->is_admin) {
                throw new Exception("You are not authorized to delete this assignment.");
            }

            return $userShift->delete();
        });
    }

    private function validateDeletionTime(UserShift $userShift): void
    {
        if ($userShift->shift_date->isPast() && !$userShift->shift_date->isToday()) {
            throw new Exception("Cannot delete a shift assignment that has already passed.");
        }
    }
}
