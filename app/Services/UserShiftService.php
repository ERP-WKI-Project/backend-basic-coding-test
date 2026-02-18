<?php

namespace App\Services;

use App\DTOs\UserShiftDto;
use App\Models\UserShift;

class UserShiftService
{
 public function list(int $perPage = 10)
    {
        return UserShift::with('user', 'shift')->latest()->paginate($perPage);
    }

    public function find(UserShift $userShift): UserShift
    {
        return $userShift->load(['user', 'shift']);
    }

    public function create(UserShiftDto $dto): UserShift
    {
        $data = $dto->toArray();

        return UserShift::create($data);
    }

    public function update(UserShift $userShift, UserShiftDto $dto): UserShift
    {
        $data = $dto->toArray();

        $userShift->update($data);

        return $userShift;
    }

    public function delete(UserShift $userShift): UserShift
    {
        $userShift->delete();

        return $userShift;
    }
}
