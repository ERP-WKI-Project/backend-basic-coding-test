<?php

namespace App\Services;

use App\DTOs\Machine\CreateMachineDto;
use App\DTOs\Machine\UpdateMachineDto;
use App\Models\Machine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MachineService
{
    public function getAll(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return Machine::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('code', 'ilike', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function create(CreateMachineDto $dto): Machine
    {
        return Machine::create($dto->toArray());
    }

    public function update(Machine $machine, UpdateMachineDto $dto): Machine
    {
        $machine->update($dto->toArray());

        return $machine->refresh();
    }

    public function delete(Machine $machine): void
    {
        $machine->delete();
    }
}
