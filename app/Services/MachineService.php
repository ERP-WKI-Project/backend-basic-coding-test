<?php

namespace App\Services;

use App\Models\Machine;
use Illuminate\Support\Facades\DB;

class MachineService
{
    /**
     * Create a new machine.
     *
     * @param array $data
     * @return Machine
     */
    public function createMachine(array $data): Machine
    {
        return DB::transaction(function () use ($data) {
            return Machine::create($data);
        });
    }

    /**
     * Update an existing machine.
     *
     * @param Machine $machine
     * @param array $data
     * @return Machine
     */
    public function updateMachine(Machine $machine, array $data): Machine
    {
        return DB::transaction(function () use ($machine, $data) {
            $machine->update($data);
            return $machine->fresh();
        });
    }

    /**
     * Delete a machine (soft delete).
     *
     * @param Machine $machine
     * @return bool
     * @throws \Exception
     */
    public function deleteMachine(Machine $machine): bool
    {
        return DB::transaction(function () use ($machine) {
            // Check if machine is being used in any active user shifts
            $hasActiveShifts = DB::table('user_shifts')
                ->where('machine_code', $machine->machine_code)
                ->whereDate('shift_date', '>=', now()->format('Y-m-d'))
                ->exists();

            if ($hasActiveShifts) {
                throw new \Exception('Tidak dapat menghapus mesin yang masih memiliki shift aktif.');
            }

            return $machine->delete();
        });
    }

    /**
     * Restore a soft-deleted machine.
     *
     * @param string $id
     * @return Machine
     */
    public function restoreMachine(string $id): Machine
    {
        return DB::transaction(function () use ($id) {
            $machine = Machine::onlyTrashed()->findOrFail($id);
            $machine->restore();
            return $machine->fresh();
        });
    }
}
