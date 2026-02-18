<?php

namespace App\Services;

use App\DTOs\UserShiftDto;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class UserShiftService
{
    /**
     * Get the currently active user shift based on time
     * Aborts with 404 if no active shift is found
     */
    public static function getActiveUserShift(User $user): UserShift
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $now = now();

        $shifts = $user->userShifts()
            ->whereIn('shift_date', [$today, $yesterday])
            ->whereNotNull('machine_code')
            ->with(['machine', 'shift'])
            ->get();

        $activeShift = $shifts->first(function ($userShift) use ($now) {
            return $now->between($userShift->shift_start, $userShift->shift_end);
        });

        abort_if(!$activeShift, 404, 'Shift tidak ditemukan');

        return $activeShift;
    }

    /**
     * Get all user shift assignments with optional filters
     */
    public static function getAllUserShifts(
        ?int $limit = null,
    ): LengthAwarePaginator {
        $query = QueryBuilder::for(UserShift::class)
            ->with(['user', 'shift', 'machine'])
            ->allowedFilters([
                AllowedFilter::callback('employee_number', function ($query, $value) {
                    $query->whereHas('user', function ($q) use ($value) {
                        $q->where('employee_number', $value);
                    });
                }),
                AllowedFilter::exact('shift_date'),
                AllowedFilter::exact('machine_code'),
            ])
            ->allowedSorts([
                'shift_date',
                'created_at',
            ])
            ->defaultSort('-shift_date');

        return $query->paginate($limit)->withQueryString();
    }

    /**
     * Assign a user to a shift with conflict validation
     *
     * @throws \Exception|\Throwable
     */
    public static function assignUserShift(UserShiftDto $dto): UserShift
    {
        return DB::transaction(function () use ($dto) {
            // Validate shift exists and get shift details
            $shift = Shift::findOrFail($dto->shift_id);

            // Check for overlapping shifts for the same user on the same date
            $hasConflict = self::checkShiftConflict(
                $dto->user_id,
                $dto->shift_id,
                $dto->shift_date,
                null // no existing shift id when creating
            );

            abort_if($hasConflict, 402, 'Karyawan sudah memiliki jadwal shift yang bertabrakan pada tanggal ini.');

            // Create the user shift assignment
            return UserShift::create($dto->toArray());
        });
    }

    /**
     * Update an existing user shift assignment
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public static function updateUserShift(UserShift $userShift, UserShiftDto $dto): UserShift
    {
        return DB::transaction(function () use ($userShift, $dto) {
            // Validate shift exists and get shift details
            $shift = Shift::findOrFail($dto->shift_id);

            // Check for overlapping shifts (exclude current shift from check)
            $hasConflict = self::checkShiftConflict(
                $dto->user_id,
                $dto->shift_id,
                $dto->shift_date,
                $userShift->id
            );

            abort_if($hasConflict, 402, 'Karyawan sudah memiliki jadwal shift yang bertabrakan pada tanggal ini.');

            // Update the shift assignment
            $userShift->update($dto->toArray());
            $userShift->refresh();
            $userShift->load(['user', 'shift']);

            // Load machine only if machine_code is set
            if ($userShift->machine_code) {
                $userShift->load('machine');
            }

            return $userShift;
        });
    }

    /**
     * Delete a user shift assignment
     */
    public static function deleteUserShift(UserShift $userShift): bool
    {
        return $userShift->delete();
    }

    /**
     * Check if a user has conflicting shifts on a given date
     * Conflict occurs when:
     * - User has another shift on the same date with overlapping times
     *
     * @param int $userId
     * @param int $shiftId
     * @param string $shiftDate
     * @param int|null $excludeShiftId - Shift ID to exclude from check (for updates)
     * @return bool
     */
    private static function checkShiftConflict(
        int $userId,
        int $shiftId,
        string $shiftDate,
        ?int $excludeShiftId = null
    ): bool {
        // Get the shift details for time comparison
        $newShift = Shift::findOrFail($shiftId);

        // Find all shifts for this user on the same date
        $existingShifts = UserShift::where('user_id', $userId)
            ->whereDate('shift_date', $shiftDate)
            ->when($excludeShiftId, function ($query) use ($excludeShiftId) {
                $query->where('id', '!=', $excludeShiftId);
            })
            ->with('shift')
            ->get();

        // Check for time overlaps
        foreach ($existingShifts as $existingUserShift) {
            $existingShift = $existingUserShift->shift;

            // Check if shifts overlap
            if (self::shiftsOverlap(
                $newShift->start_time,
                $newShift->end_time,
                $existingShift->start_time,
                $existingShift->end_time
            )) {
                return true; // Conflict found
            }
        }

        return false; // No conflict
    }

    /**
     * Check if two time ranges overlap
     *
     * @param string $start1
     * @param string $end1
     * @param string $start2
     * @param string $end2
     * @return bool
     */
    private static function shiftsOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        $start1 = Carbon::parse($start1);
        $end1 = Carbon::parse($end1);
        $start2 = Carbon::parse($start2);
        $end2 = Carbon::parse($end2);

        // Handle overnight shifts (end time is before start time)
        if ($end1->lessThan($start1)) {
            $end1->addDay();
        }
        if ($end2->lessThan($start2)) {
            $end2->addDay();
        }

        // Check if ranges overlap
        return $start1->lessThan($end2) && $end1->greaterThan($start2);
    }
}

