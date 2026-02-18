<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class UserService
{
    public static function getAllUsers(?int $limit = null, ?string $search = null): LengthAwarePaginator
    {
        $query = QueryBuilder::for(User::class)
            ->allowedFilters([
                AllowedFilter::exact('employee_number'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('email'),
                AllowedFilter::trashed(),
            ])
            ->allowedSorts([
                'employee_number',
                'name',
                'email',
                'created_at',
            ])
            ->defaultSort('-created_at');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('employee_number', 'LIKE', "%{$search}%");
            });
        }

        return $query->paginate($limit)->withQueryString();
    }

    public static function createUser(UserDto $dto): User
    {
        $data = $dto->toArray();

        // Auto-generate employee number from the latest one
        $data['employee_number'] = self::generateEmployeeNumber();

        // Set default password if not provided
        if (empty($data['password'])) {
            $data['password'] = Hash::make(config('app.default_user_password'));
        }

        return User::create($data);
    }

    public static function updateUser(User $user, UserDto $dto): User
    {
        $data = $dto->toArray();

        // Hash password if provided during update
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return $user->fresh();
    }

    public static function deleteUser(User $user): bool
    {
        return $user->delete();
    }

    private static function generateEmployeeNumber(): string
    {
        $latestUser = User::withTrashed()
            ->orderBy('employee_number', 'desc')
            ->first();

        if (!$latestUser) {
            // Start from 000001 if no users exist
            return '000001';
        }

        // Increment the latest employee number
        $nextNumber = (int) $latestUser->employee_number + 1;

        // Format to 6 digits with leading zeros
        return str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}

