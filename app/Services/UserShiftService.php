<?php

namespace App\Services;

use App\Models\UserShift;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class UserShiftService extends BaseService
{
    protected string $modelClass = UserShift::class;

    protected string $resourceName = 'UserShift';

    protected function applyFilters($query, array $filters)
    {
        // Eager load relations
        $query->with(['user', 'shift', 'machine']);

        // Filter by user_id
        if (isset($filters['user_id']) && ! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by machine_id
        if (isset($filters['machine_id']) && ! empty($filters['machine_id'])) {
            $query->where('machine_id', $filters['machine_id']);
        }

        // Filter by date range
        if (isset($filters['from_date']) && ! empty($filters['from_date'])) {
            $query->whereDate('shift_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date']) && ! empty($filters['to_date'])) {
            $query->whereDate('shift_date', '<=', $filters['to_date']);
        }

        // Filter by specific date
        if (isset($filters['date']) && ! empty($filters['date'])) {
            $query->whereDate('shift_date', $filters['date']);
        }

        return $query;
    }

    public function create(array $data): UserShift
    {
        // Check for duplicate assignment (same user + date)
        $exists = $this->modelClass::where('user_id', $data['user_id'])
            ->whereDate('shift_date', $data['shift_date'])
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException('User already has a shift assigned on this date');
        }

        return parent::create($data);
    }

    public function getActiveShiftsForUser(int $userId, string $date): Collection
    {
        return UserShift::with(['user', 'shift', 'machine'])
            ->where('user_id', $userId)
            ->whereDate('shift_date', $date)
            ->get();
    }
}
