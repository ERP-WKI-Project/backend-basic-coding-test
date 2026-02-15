<?php

namespace App\Services;

use App\DTOs\User\CreateUserDto;
use App\DTOs\User\UpdateUserDto;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function getAll(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return User::query()
            ->when($search, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('employee_number', 'ilike', "%{$search}%");
            }))
            ->orderBy('employee_number')
            ->paginate($perPage);
    }

    public function create(CreateUserDto $dto): User
    {
        return User::create($dto->toArray());
    }

    public function update(User $user, UpdateUserDto $dto): User
    {
        $user->update($dto->toArray());

        return $user->refresh();
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }
}
