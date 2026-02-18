<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function list(int $perPage = 10, int $page = 1)
    {
        $cacheKey = "users:list:perPage={$perPage}:page={$page}";

        return Cache::tags(['users'])->remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($perPage, $page) {
                return User::latest()->paginate($perPage, ['*'], 'page', $page);
            }
        );
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
