<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Get a paginated list of users with caching
     *
     * @param  int  $perPage  Number of users per page (default 10)
     * @param  int  $page  Current page number (default 1)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(int $perPage = 10, int $page = 1)
    {
        // Generate a unique cache key based on perPage and page number
        $cacheKey = "users:list:perPage={$perPage}:page={$page}";

        // Use Laravel cache with tags to cache paginated users for 5 minutes
        // Tags allow clearing all 'users' cache at once if needed
        return Cache::tags(['users'])->remember(
            $cacheKey,
            now()->addMinutes(5), // Cache expiration
            function () use ($perPage, $page) {
                // Retrieve users ordered by newest first and paginate
                return User::latest()->paginate($perPage, ['*'], 'page', $page);
            }
        );
    }

    /**
     * Find a specific user
     *
     * @param  User  $user  The user model instance
     */
    public function find(User $user): User
    {
        // Simply return the user instance (typically injected via route-model binding)
        return $user;
    }

    /**
     * Create a new user
     *
     * @param  UserDto  $dto  DTO containing validated user data
     * @return User The newly created user
     */
    public function create(UserDto $dto): User
    {
        // Convert the DTO into an array for mass assignment
        $data = $dto->toArray();

        // Hash the password if it exists before saving
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Create and return the user
        return User::create($data);
    }

    /**
     * Update an existing user
     *
     * @param  User  $user  The user to update
     * @param  UserDto  $dto  DTO containing updated user data
     * @return User The updated user
     */
    public function update(User $user, UserDto $dto): User
    {
        // Convert the DTO into an array
        $data = $dto->toArray();

        // Hash the password if it exists
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Update the user record
        $user->update($data);

        // Return the updated user
        return $user;
    }

    /**
     * Delete a user
     *
     * @param  User  $user  The user to delete
     * @return User The deleted user instance (soft delete assumed if SoftDeletes trait is used)
     */
    public function delete(User $user): User
    {
        // Delete the user (soft delete if SoftDeletes trait is used)
        $user->delete();

        // Return the deleted user instance
        return $user;
    }
}
