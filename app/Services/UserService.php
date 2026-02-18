<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{

    public function createUser(array $data): UserDto
    {
        $user = User::createUser($data);
        return UserDto::fromModel($user);
    }


    public function getUserByNik(string $nik): ?UserDto
    {
        $user = User::getByNik($nik);

        if (!$user) {
            return null;
        }

        return UserDto::fromModel($user);
    }

    public function updateUserByNik(string $nik, array $data): ?UserDto
    {
        $user = User::getByNik($nik);

        if (!$user) {
            return null;
        }

        $updatedUser = User::updateUserByNik($nik, $data);

        return UserDto::fromModel($updatedUser);
    }

    public function getUserListFormatted(int $perPage = 15, int $page = 1)
    {
        $paginator = User::getUserListPaginated(perPage: $perPage, columns: ['*']);

        return $paginator;
    }

    public function deleteUserByNik(string $nik): bool
    {
        $user = User::getByNik($nik);

        if (!$user) {
            return false;
        }

        return User::deleteUserByNik($nik);
    }
}
