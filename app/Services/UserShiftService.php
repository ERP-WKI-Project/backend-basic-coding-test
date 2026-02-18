<?php

namespace App\Services;

use App\DTOs\UserShiftDto;
use App\Models\UserShift;
use Illuminate\Pagination\LengthAwarePaginator;

class UserShiftService
{
    public function getAll(int $perPage = 10): LengthAwarePaginator
    {
        return UserShift::query()
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): UserShift
    {
        return UserShift::findOrFail($id);
    }

    public function create(UserShiftDto $dto): UserShift
    {
        $data = $dto->toArray();

        return UserShift::create($data);
    }

    public function update(int $id, UserShiftDto $dto): UserShift
    {
        $userShift = $this->findById($id);
        $data = $dto->toArray();

        $userShift->update($data);

        return $userShift;
    }

    public function delete(int $id): void
    {
        $userShift = $this->findById($id);
        $userShift->delete();
    }
}
