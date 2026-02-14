<?php

namespace App\Services;

use App\Models\Machine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class MachineService
{
    public function getPaginatedMachines(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Machine::latest()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('code', 'ilike', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->paginate($perPage);
    }

    public function createMachine(array $data): Machine
    {
        DB::beginTransaction();
        try {
            $machine = Machine::create($data);
            DB::commit();
            return $machine;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateMachine(Machine $machine, array $data): Machine
    {
        DB::beginTransaction();
        try {
            $machine->update($data);
            DB::commit();
            return $machine;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteMachine(Machine $machine): void
    {
        DB::beginTransaction();
        try {
            $machine->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
