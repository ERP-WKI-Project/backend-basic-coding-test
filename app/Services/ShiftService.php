<?php

namespace App\Services;

use App\DTOs\ShiftDto;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    /**
     * Get all shifts
     */
    public function getAllShifts(): Collection
    {
        return Shift::with('userShifts')->get();
    }

    /**
     * Get shift by ID
     */
    public function getShiftById(int $id): Shift
    {
        return Shift::with('userShifts')->findOrFail($id);
    }

    /**
     * Get shifts by day of week
     */
    public function getShiftsByDayOfWeek(int $dayOfWeek): Collection
    {
        return Shift::where('day_of_week', $dayOfWeek)->get();
    }

    /**
     * Create new shift with overlap validation
     */
    public function createShift(ShiftDto $dto): Shift
    {
        // Validate no overlapping shifts for the same day_of_week
        $this->validateNoOverlap(
            $dto->dayOfWeek,
            $dto->startTime,
            $dto->endTime
        );

        return Shift::create($dto->toArray());
    }

    /**
     * Update shift with overlap validation
     */
    public function updateShift(int $id, ShiftDto $dto): Shift
    {
        $shift = Shift::findOrFail($id);

        // Validate no overlapping shifts for the same day_of_week (excluding current shift)
        $this->validateNoOverlap(
            $dto->dayOfWeek,
            $dto->startTime,
            $dto->endTime,
            $id
        );

        $shift->update($dto->toArray());

        return $shift->fresh();
    }

    /**
     * Delete shift (check dependencies first)
     */
    public function deleteShift(int $id): bool
    {
        $shift = Shift::findOrFail($id);

        // Check if shift has any user assignments
        if ($shift->userShifts()->exists()) {
            throw ValidationException::withMessages([
                'shift_id' => 'Cannot delete shift that has user assignments. Please remove all assignments first.',
            ]);
        }

        return $shift->delete();
    }

    /**
     * Validate that new shift doesn't overlap with existing shifts on the same day
     */
    private function validateNoOverlap(
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeShiftId = null
    ): void {
        $query = Shift::where('day_of_week', $dayOfWeek)
            ->where(function ($q) use ($startTime, $endTime) {
                // Check for any time overlap
                // Case 1: New shift starts during existing shift
                $q->where(function ($query) use ($startTime) {
                    $query->where('start_time', '<=', $startTime)
                        ->where('end_time', '>', $startTime);
                })
                // Case 2: New shift ends during existing shift
                ->orWhere(function ($query) use ($endTime) {
                    $query->where('start_time', '<', $endTime)
                        ->where('end_time', '>=', $endTime);
                })
                // Case 3: New shift completely contains existing shift
                ->orWhere(function ($query) use ($startTime, $endTime) {
                    $query->where('start_time', '>=', $startTime)
                        ->where('end_time', '<=', $endTime);
                });
            });

        if ($excludeShiftId) {
            $query->where('id', '!=', $excludeShiftId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'time' => 'Shift time overlaps with an existing shift on the same day of week.',
            ]);
        }
    }
}
