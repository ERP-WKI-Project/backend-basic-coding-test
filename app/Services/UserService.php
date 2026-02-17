<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public static function getAllUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::latest()->paginate($perPage);
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

