<?php

namespace App\Services\BackOffice;

use App\DTOs\ShiftDto;
use App\DTOs\UserShiftDto;
use App\Models\Shift;
use App\Models\UserShift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ShiftService
{
    public function getPaginatedShifts(int $limit = 10, ?string $search = null, ?int $dayOfWeek = null): LengthAwarePaginator
    {
        $query = Shift::query();

        if ($search !== null && $search !== '') {
            $query->where('name', 'ilike', '%'.$search.'%');
        }

        if ($dayOfWeek !== null) {
            $query->where('day_of_week', $dayOfWeek);
        }

        return $query->orderBy('day_of_week')->orderBy('start_time')->paginate($limit);
    }

    public function createShift(ShiftDto $dto): Shift
    {
        return Shift::create([
            'ulid' => Str::ulid(),
            'name' => $dto->name,
            'day_of_week' => $dto->dayOfWeek,
            'start_time' => $dto->startTime,
            'end_time' => $dto->endTime,
        ]);
    }

    public function updateShift(Shift $shift, ShiftDto $dto): Shift
    {
        $data = $dto->toArray();

        foreach ($data as $key => $value) {
            if ($value === '' || $value === null) {
                unset($data[$key]);
            }
        }

        if (! empty($data)) {
            $shift->update($data);
        }

        return $shift->fresh();
    }

    public function deleteShift(Shift $shift): bool
    {
        return $shift->delete();
    }

    public function assignUserShift(UserShiftDto $dto): UserShift
    {
        return UserShift::create([
            'user_id' => $dto->userId,
            'shift_id' => $dto->shiftId,
            'shift_date' => $dto->shiftDate,
            'machine_code' => $dto->machineCode,
        ]);
    }

    public function updateUserShift(UserShift $userShift, UserShiftDto $dto): UserShift
    {
        $userShift->update($dto->toArray());

        return $userShift->fresh();
    }

    public function deleteUserShift(UserShift $userShift): bool
    {
        return $userShift->delete();
    }

    public function getUserShift(int $id): ?UserShift
    {
        return UserShift::with(['shift', 'user'])->find($id);
    }

    public function getUserShiftsByDate(string $date, ?int $userId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = UserShift::with(['shift', 'user', 'machine']);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->whereDate('shift_date', $date)->get();
    }
}
