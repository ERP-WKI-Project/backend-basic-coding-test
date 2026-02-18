<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;

class UserService
{
    protected User $model;

    /**
     * Create a new class instance.
     */
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function getPaginatedUsers(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->latest()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                    $q->where('name', $operator, "%{$search}%")
                        ->orWhere('employee_number', $operator, "%{$search}%")
                        ->orWhere('email', $operator, "%{$search}%");
                });
            })
            ->paginate($perPage);
    }

    public function createUser(UserDto $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->model->create($data->toArray());

            return $user;
        });
    }

    public function updateUser(User $user, UserDto $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $payload = $data->toArray();

            if (empty($payload['password'])) {
                unset($payload['password']);
            }

            $user->update($payload);

            return $user;
        });
    }

    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            return $user->delete();
        });
    }
}
