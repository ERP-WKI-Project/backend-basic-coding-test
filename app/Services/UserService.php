<?php

namespace App\Services;

use App\DTOs\CreateUserDto;
use App\DTOs\UpdateUserDto;
use App\Models\User;
use App\Http\Resources\BackOffice\UserResource;

class UserService
{
    public static function createUser(CreateUserDto $dto): User
    {
        $userData = User::create([
            'employee_number' => rand(100, 999). rand(100, 999),
            'name' => $dto->name,
            'email' => $dto->email. rand(),
            'password' => $dto->password,
        ]);
        return new UserResource($userData);
    }

    public static function getUser($id): User
    {
        $user = User::find($id);

        return new UserResource($user);
    }

    public static function getAllUser()
    {
        $user = User::get();

        return UserResource::collection($user);
    }

    public static function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return null;
        }
        
        $user->delete();

        return $user;
    }

    public static function updateUser(UpdateUserDto $dto, $id): User
    {
        $userData = User::find($id);

        $userData->update([
            'name' => $dto->name,
            'email' => $dto->email,
        ]);

        $userData = User::find($id);

        return new UserResource($userData);
    }
}
