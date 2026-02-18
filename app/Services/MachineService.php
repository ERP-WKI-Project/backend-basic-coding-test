<?php

namespace App\Services;

use App\DTOs\MachineDto;
use App\Models\Machine;
use Illuminate\Support\Facades\Cache;

class MachineService
{
    /**
     * Get a paginated list of machines with caching
     *
     * @param  int  $perPage  Number of items per page (default 10)
     * @param  int  $page  Current page number (default 1)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(int $perPage = 10, int $page = 1)
    {
        // Generate a unique cache key for this page and perPage combination
        $cacheKey = "machines:list:perPage={$perPage}:page={$page}";

        // Use Laravel's cache with tags to store the paginated machine list for 5 minutes
        // Tags allow you to clear cache for all 'machines' if needed
        return Cache::tags(['machines'])->remember(
            $cacheKey,
            now()->addMinutes(5), // Cache expiration time
            fn () => Machine::latest()            // Order machines by newest first
                ->paginate($perPage, ['*'], 'page', $page) // Paginate results
        );
    }

    /**
     * Find a specific machine
     *
     * @param  Machine  $machine  The machine model instance
     */
    public function find(Machine $machine): Machine
    {
        // Simply return the provided Machine model
        // The controller can use route-model binding to inject the machine
        return $machine;
    }

    /**
     * Create a new machine
     *
     * @param  MachineDto  $dto  Data Transfer Object with validated machine data
     * @return Machine The newly created Machine model
     */
    public function create(MachineDto $dto): Machine
    {
        // Convert the DTO to an array for mass assignment
        $data = $dto->toArray();

        // Create and return a new Machine record in the database
        return Machine::create($data);
    }

    /**
     * Update an existing machine
     *
     * @param  Machine  $machine  The machine to update
     * @param  MachineDto  $dto  DTO containing the updated data
     * @return Machine The updated Machine model
     */
    public function update(Machine $machine, MachineDto $dto): Machine
    {
        // Convert the DTO to an array for updating
        $data = $dto->toArray();

        // Update the machine record
        $machine->update($data);

        // Return the updated machine
        return $machine;
    }

    /**
     * Delete a machine
     *
     * @param  Machine  $machine  The machine to delete
     * @return Machine The deleted machine instance (soft delete assumed)
     */
    public function delete(Machine $machine): Machine
    {
        // Delete the machine (soft delete if 'SoftDeletes' trait is used)
        $machine->delete();

        // Return the deleted machine
        return $machine;
    }
}
