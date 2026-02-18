<?php

namespace App\Services;

use App\Models\Machine;
use Illuminate\Support\Facades\DB;

class MachineService extends BaseService
{
    protected string $modelClass = Machine::class;

    protected string $resourceName = 'Machine';

    protected function applyFilters($query, array $filters)
    {
        // Filter by status
        if (isset($filters['status']) && ! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Search filter
        if (isset($filters['search']) && ! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function findByCode(string $code): ?Machine
    {
        return Machine::where('code', $code)->first();
    }

    public function isMachineInUse(int $machineId): bool
    {
        return DB::table('user_shifts')
            ->where('machine_id', $machineId)
            ->whereDate('shift_date', '>=', now()->format('Y-m-d'))
            ->exists();
    }
}
