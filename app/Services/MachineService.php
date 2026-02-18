<?php

namespace App\Services;

use App\DTOs\MachineDto;
use App\Models\Machine;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class MachineService
{
    public static function getAllMachines(?int $limit = null, ?string $search = null): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Machine::class)
            ->allowedFilters([
                AllowedFilter::exact('machine_code'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('description'),
                AllowedFilter::exact('status'),
                AllowedFilter::trashed(),
            ])
            ->allowedSorts([
                'machine_code',
                'name',
                'status',
                'created_at',
            ])
            ->defaultSort('-created_at');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('machine_code', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        return $query->paginate($limit)->withQueryString();
    }

    public static function createMachine(MachineDto $dto): Machine
    {
        $data = $dto->toArray();

        // Auto-generate machine code from the latest one
        $data['machine_code'] = self::generateMachineCode();

        return Machine::create($data);
    }

    public static function updateMachine(Machine $machine, MachineDto $dto): Machine
    {
        $data = $dto->toArray();

        $machine->update($data);
        return $machine->fresh();
    }

    public static function deleteMachine(Machine $machine): bool
    {
        return $machine->delete();
    }

    private static function generateMachineCode(): string
    {
        $latestMachine = Machine::withTrashed()
            ->orderBy('machine_code', 'desc')
            ->first();

        if (!$latestMachine) {
            // Start from MACHINE-001 if no machines exist
            return 'MACHINE-001';
        }

        // Extract number from machine code (e.g., MACHINE-001 -> 001)
        $parts = explode('-', $latestMachine->machine_code);
        $lastNumber = (int) end($parts);

        // Increment the number
        $nextNumber = $lastNumber + 1;

        // Format to 3 digits with leading zeros
        return 'MACHINE-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}

