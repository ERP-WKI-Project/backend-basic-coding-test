<?php

namespace App\Services;

use App\DTOs\ShiftDto;
use App\Models\Shift;

class ShiftService
{
    /**
     * Create a new shift
     */
    public function createShift(array $data): ShiftDto
    {
        $shift = Shift::createShift($data);
        return ShiftDto::fromModel($shift);
    }

    /**
     * Get shift detail by id
     */
    public function getShiftById(int $id): ?ShiftDto
    {
        $shift = Shift::getShiftById($id);

        if (!$shift) {
            return null;
        }

        return ShiftDto::fromModel($shift);
    }

    /**
     * Update shift by id
     */
    public function updateShiftById(int $id, array $data): ?ShiftDto
    {
        $shift = Shift::getShiftById($id);

        if (!$shift) {
            return null;
        }

        $updatedShift = Shift::updateShiftById($id, $data);

        return ShiftDto::fromModel($updatedShift);
    }

    /**
     * Get list of shifts with pagination
     */
    public function getShiftListFormatted(int $perPage = 15, int $page = 1): array
    {
        $paginator = Shift::getListPaginated($perPage, $page);

        return [
            'shifts' => $paginator->getCollection()->map(fn($shift) => ShiftDto::fromModel($shift)->toArray()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
    }

    /**
     * Get shifts by day of week with pagination
     */
    public function getShiftsByDayOfWeekFormatted(int $dayOfWeek, int $perPage = 15, int $page = 1): array
    {
        $paginator = Shift::getShiftsByDayOfWeekPaginated($dayOfWeek, $perPage, $page);

        return [
            'shifts' => $paginator->getCollection()->map(fn($shift) => ShiftDto::fromModel($shift)->toArray()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
    }

    /**
     * Delete shift by id
     */
    public function deleteShiftById(int $id): bool
    {
        $shift = Shift::getShiftById($id);

        if (!$shift) {
            return false;
        }

        return Shift::deleteShiftById($id);
    }
}
