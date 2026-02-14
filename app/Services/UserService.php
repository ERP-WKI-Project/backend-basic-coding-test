<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getAll(int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): User
    {
        return User::findOrFail($id);
    }

    public function create(UserDTO $dto): User
    {
        $data = $dto->toArray();
        $data['password'] = Hash::make($dto->password);

        return User::create($data);
    }

    public function update(int $id, UserDTO $dto): User
    {
        $user = $this->findById($id);
        $data = $dto->toArray();

        if (!empty($dto->password)) {
            $data['password'] = Hash::make($dto->password);
        }

        $user->update($data);

        return $user;
    }

    public function delete(int $id): void
    {
        $user = $this->findById($id);
        $user->delete();
    }
}
