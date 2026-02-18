<?php

namespace App\Services;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Collection;

class ShiftService extends BaseService
{
    protected string $modelClass = Shift::class;

    protected string $resourceName = 'Shift';

    protected function applyFilters($query, array $filters)
    {
        // Filter by day_of_week
        if (isset($filters['day_of_week']) && ! empty($filters['day_of_week'])) {
            $query->where('day_of_week', $filters['day_of_week']);
        }

        return $query;
    }

    public function getShiftsByDay(int $dayOfWeek): Collection
    {
        return Shift::where('day_of_week', $dayOfWeek)
            ->orderBy('start_time')
            ->get();
    }
}
