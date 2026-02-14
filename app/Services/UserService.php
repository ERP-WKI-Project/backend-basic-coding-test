<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Create a new user with validation and business logic.
     *
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Hash password before storing
            $data['password'] = Hash::make($data['password']);

            // Create user
            $user = User::create($data);

            // Additional business logic can be added here
            // Example: Send welcome email, log activity, etc.

            return $user;
        });
    }

    /**
     * Update existing user with validation and business logic.
     *
     * @param User $user
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Hash password if provided
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            // Update user
            $user->update($data);

            // Refresh to get latest data
            $user->refresh();

            // Additional business logic can be added here
            // Example: Log changes, notify user, etc.

            return $user;
        });
    }

    /**
     * Soft delete user with validation.
     *
     * @param User $user
     * @return bool
     * @throws \Exception
     */
    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // Business logic validation
            // Example: Check if user has active shifts
            $hasActiveShifts = $user->userShifts()
                ->whereDate('shift_date', '>=', now()->format('Y-m-d'))
                ->exists();

            if ($hasActiveShifts) {
                throw new \Exception('Tidak dapat menghapus user yang memiliki shift aktif');
            }

            // Soft delete user
            return $user->delete();
        });
    }

    /**
     * Restore soft deleted user.
     *
     * @param string $employeeNumber
     * @return User
     */
    public function restoreUser(string $employeeNumber): User
    {
        $user = User::withTrashed()
            ->where('employee_number', $employeeNumber)
            ->firstOrFail();

        $user->restore();

        return $user;
    }
}
