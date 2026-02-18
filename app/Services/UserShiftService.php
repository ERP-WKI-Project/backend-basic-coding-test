<?php

namespace App\Services;

use App\DTOs\UserShiftDto;
use App\Models\UserShift;

class UserShiftService
{
    /**
     * Get a paginated list of user shifts
     *
     * @param  int  $perPage  Number of items per page (default 10)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(int $perPage = 10)
    {
        // Retrieve all UserShift records ordered by newest first
        // Eager load the related 'user' and 'shift' to avoid N+1 query problem
        // Paginate results with $perPage items per page
        return UserShift::with('user', 'shift')->latest()->paginate($perPage);
    }

    /**
     * Find a specific user shift
     *
     * @param  UserShift  $userShift  The user shift instance
     * @return UserShift The user shift with related user and shift loaded
     */
    public function find(UserShift $userShift): UserShift
    {
        // Load related 'user' and 'shift' relationships for this UserShift
        return $userShift->load(['user', 'shift']);
    }

    /**
     * Create a new user shift
     *
     * @param  UserShiftDto  $dto  DTO containing validated user shift data
     * @return UserShift The newly created UserShift
     */
    public function create(UserShiftDto $dto): UserShift
    {
        // Convert the DTO into an array for mass assignment
        $data = $dto->toArray();

        // Create and return the new UserShift record
        return UserShift::create($data);
    }

    /**
     * Update an existing user shift
     *
     * @param  UserShift  $userShift  The user shift to update
     * @param  UserShiftDto  $dto  DTO containing updated data
     * @return UserShift The updated UserShift
     */
    public function update(UserShift $userShift, UserShiftDto $dto): UserShift
    {
        // Convert DTO into array
        $data = $dto->toArray();

        // Update the UserShift record
        $userShift->update($data);

        // Return the updated record
        return $userShift;
    }

    /**
     * Delete a user shift
     *
     * @param  UserShift  $userShift  The user shift to delete
     * @return UserShift The deleted UserShift instance
     */
    public function delete(UserShift $userShift): UserShift
    {
        // Delete the record (soft delete if SoftDeletes trait is used)
        $userShift->delete();

        // Return the deleted instance
        return $userShift;
    }
}
