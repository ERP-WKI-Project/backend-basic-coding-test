<?php

namespace App\Services;

use App\DTOs\UserShift\CreateUserShiftDto;
use App\DTOs\UserShift\UpdateUserShiftDto;
use App\Models\UserShift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserShiftService
{
    public function getAll(int $perPage = 15, ?array $filters = [], ?string $search = null): LengthAwarePaginator
    {
        return UserShift::query()
            ->when($filters['user_id'] ?? null, fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when($filters['shift_id'] ?? null, fn ($query) => $query->where('shift_id', $filters['shift_id']))
            ->when($filters['shift_date'] ?? null, fn ($query) => $query->where('shift_date', $filters['shift_date']))
            ->when($search, fn ($query) => $query->where('machine_code', 'like', "%{$search}%"))
            ->with(['user', 'shift', 'machine'])
            ->orderBy('shift_date', 'desc')
            ->paginate($perPage);
    }

    public function create(CreateUserShiftDto $dto): UserShift
    {
        return UserShift::create($dto->toArray());
    }

    public function update(UserShift $userShift, UpdateUserShiftDto $dto): UserShift
    {
        $userShift->update($dto->toArray());

        return $userShift->refresh();
    }

    public function delete(UserShift $userShift): bool
    {
        return $userShift->delete();
    }
}
