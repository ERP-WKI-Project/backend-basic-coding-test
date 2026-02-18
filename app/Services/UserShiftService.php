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
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('machine_code', 'ilike', "%{$search}%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'ilike', "%{$search}%")
                                ->orWhere('employee_number', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('shift', function ($q) use ($search) {
                            $q->where('name', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('machine', function ($q) use ($search) {
                            $q->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->with(['user', 'shift', 'machine'])
            ->orderBy('shift_date', 'desc')
            ->paginate($perPage);
    }

    public function create(CreateUserShiftDto $dto): UserShift
    {
        $userShift = UserShift::create($dto->toArray());

        return $userShift->load(['user', 'shift', 'machine']);
    }

    public function update(UserShift $userShift, UpdateUserShiftDto $dto): UserShift
    {
        $userShift->update($dto->toArray());

        return $userShift->refresh()->load(['user', 'shift', 'machine']);
    }

    public function delete(UserShift $userShift): bool
    {
        return $userShift->delete();
    }
}
