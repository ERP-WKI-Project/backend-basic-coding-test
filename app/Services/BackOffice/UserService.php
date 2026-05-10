<?php

namespace App\Services\BackOffice;

use App\DTOs\UserDto;
use App\Models\User;

class UserService
{
    /**
     * Get paginated users with optional search across name, email, and employee_number.
     *
     * @param int $limit Items per page (default 10)
     * @param string|null $search Search term for filtering (partial match on name, email, employee_number)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedUsers(int $limit = 10, ?string $search = null)
    {
        $query = User::query();

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%'.$search.'%')
                    ->orWhere('email', 'ilike', '%'.$search.'%')
                    ->orWhere('employee_number', 'ilike', '%'.$search.'%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Create a new user from DTO.
     *
     * @param UserDto $dto Data transfer object containing employee_number, name, email, password
     * @return User
     */
    public function createUser(UserDto $dto): User
    {
        return User::create([
            'employee_number' => $dto->employee_number,
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
        ]);
    }

    /**
     * Update user with DTO data. Password is omitted if empty.
     *
     * @param User $user User model to update
     * @param UserDto $dto Data transfer object with updated fields
     * @return User Updated user instance
     */
    public function updateUser(User $user, UserDto $dto): User
    {
        $payload = $dto->toArray();

        if (empty($payload['password'])) {
            unset($payload['password']);
        }

        $user->update($payload);

        return $user->fresh();
    }

    /**
     * Soft delete a user.
     *
     * @param User $user User model to delete
     * @return bool Result of delete operation
     */
    public function deleteUser(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Restore a soft-deleted user.
     *
     * @param int $id User ID to restore
     * @return User|null Restored user or null if not found
     */
    public function restoreUser(int $id): ?User
    {
        $user = User::withTrashed()->find($id);

        if (! $user) {
            return null;
        }

        $user->restore();

        return $user->fresh();
    }
}
