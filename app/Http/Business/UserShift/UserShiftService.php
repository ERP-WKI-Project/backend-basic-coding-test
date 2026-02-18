<?php

namespace App\Http\Business\UserShift;

use App\DTOs\UserShiftDto;
use App\Models\Machine;
use App\Models\UserShift;
use App\Models\User;
use App\Models\Shift;

class UserShiftService
{
    /**
     * Assign user shift (create new user shift)
     */
    public function assignUserShift(array $data): ?UserShiftDto
    {
        // Validate user exists
        $user = User::getByNik($data['nik']);
        if (!$user) {
            return null;
        }

        // Validate shift exists
        $shift = Shift::getShiftById($data['shift_id']);
        if (!$shift) {
            return null;
        }

        // Validate machine exists
        $machine = Machine::getByMachineCode($data['machine_code']);
        if (!$machine) {
            return null;
        }

        $data['user_id'] = $user->id;
        unset($data['nik']);

        $userShift = UserShift::createUserShift($data);
        return UserShiftDto::fromModel($userShift->load('shift', 'user', 'machine'));
    }

    /**
     * Get user shift detail by id
     */
    public function getUserShiftById(int $id): ?UserShiftDto
    {
        $userShift = UserShift::getWithRelationsById($id);

        if (!$userShift) {
            return null;
        }

        return UserShiftDto::fromModel($userShift);
    }

    /**
     * Update user shift by id (update machine code and/or shift date)
     * Returns: UserShiftDto on success, null on not found, false on no data to update
     */
    public function updateUserShift(int $id, array $data)
    {
        // Check if user shift exists
        $userShift = UserShift::getWithRelationsById($id);
        if (!$userShift) {
            return null; // Not found
        }

        // Validate shift if being updated
        if (isset($data['shift_id'])) {
            if (!Shift::getShiftById($data['shift_id'])) {
                return false; // Shift not found - consider as validation error
            }
        }

        // Build update data (only include fields that are provided)
        $updateData = [];
        if (isset($data['shift_id'])) {
            $updateData['shift_id'] = $data['shift_id'];
        }
        if (isset($data['machine_code'])) {
            $updateData['machine_code'] = $data['machine_code'];
        }
        if (isset($data['shift_date'])) {
            $updateData['shift_date'] = $data['shift_date'];
        }

        // Return false if no data to update
        if (empty($updateData)) {
            return false; // No data to update
        }

        $updatedUserShift = UserShift::updateUserShiftById($id, $updateData);
        return UserShiftDto::fromModel($updatedUserShift->load('shift', 'user', 'machine'));
    }

    /**
     * Delete user shift by id
     * Returns: true on success, false on not found
     */
    public function deleteUserShift(int $id): bool
    {
        // Check if user shift exists first
        if (!UserShift::existsById($id)) {
            return false; // Not found
        }

        return UserShift::deleteUserShiftById($id);
    }

    /**
     * Get user shifts with pagination and optional filters
     * Can filter by userId, date, or date range
     */
    public function getUserShiftsFormatted(
        int $perPage = 15,
        int $page = 1,
        ?int $userId = null,
        ?\DateTime $date = null,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): ?array {

        $paginator = UserShift::getWithRelationsPaginated($perPage, $page, $userId, $date, $startDate, $endDate);

        return [
            'user_shifts' => $paginator->getCollection()->map(fn($userShift) => UserShiftDto::fromModel($userShift)->toArray()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
    }
}
