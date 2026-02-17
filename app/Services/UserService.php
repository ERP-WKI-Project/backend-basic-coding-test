<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function list(int $perPage = 10)
    {
        return User::latest()->paginate($perPage);
    }

    public function find(User $user): User
    {
        return $user;
    }

    public function create(UserDto $dto): User
    {
        $data = $dto->toArray();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return User::create($data);
    }

    public function update(User $user, UserDto $dto): User
    {
        $data = $dto->toArray();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return $user;
    }

    public function delete(User $user): User
    {
        $user->delete();

        return $user;
    }
}
