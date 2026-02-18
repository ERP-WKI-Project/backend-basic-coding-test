<?php

namespace App\Services;

use App\DTOs\MachineDto;
use App\Models\Machine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class MachineService
{
    protected Machine $model;

    /**
     * Create a new class instance.
     */
    public function __construct(Machine $model)
    {
        $this->model = $model;
    }

    public function getPaginatedMachines(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->latest()
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

    public function createMachine(MachineDto $data): Machine
    {
        return DB::transaction(function () use ($data) {
            $machine = $this->model->create($data->toArray());

            return $machine;
        });
    }

    public function updateMachine(Machine $machine, MachineDto $data): Machine
    {
        return DB::transaction(function () use ($machine, $data) {
            $machine->update($data->toArray());

            return $machine;
        });
    }

    public function deleteMachine(Machine $machine): bool
    {
        return DB::transaction(function () use ($machine) {
            return $machine->delete();
        });
    }
}
