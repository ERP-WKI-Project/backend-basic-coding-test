<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\MachineDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\StoreMachineRequest;
use App\Http\Requests\BackOffice\UpdateMachineRequest;
use App\Http\Resources\BackOffice\MachineResource;
use App\Models\Machine;
use App\Services\MachineService;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function __construct(
        private MachineService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get the 'per_page' parameter from the request or default to 10
        $perPage = $request->get('per_page', 10);

        // Get the 'page' parameter from the request or default to 1
        $page = $request->get('page', 1);

        // Call the service layer to get a paginated list of machines
        $machines = $this->service->list($perPage, $page);

        // Return the list of machines as a JSON response
        return response()->json($machines);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMachineRequest $request)
    {
        // Validate the request and convert it into a Data Transfer Object (DTO)
        $dto = MachineDto::fromArray($request->validated());

        // Pass the DTO to the service layer to create a new machine
        $machine = $this->service->create($dto);

        // Return the created machine as a resource with HTTP status 201 (Created)
        return (new MachineResource($machine))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Machine $machine)
    {
        // Use the service layer to find the machine details
        $machine = $this->service->find($machine);

        // Return the machine details as a resource
        return new MachineResource($machine);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMachineRequest $request, Machine $machine)
    {
        // Validate the request and convert it into a DTO
        $dto = MachineDto::fromArray($request->validated());

        // Update the machine using the service layer with the provided DTO
        $machine = $this->service->update($machine, $dto);

        // Return the updated machine as a resource
        return new MachineResource($machine);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Machine $machine)
    {
        // Soft delete the machine using the service layer
        $machine = $this->service->delete($machine);

        // Return a JSON response indicating successful deletion, including the deleted machine's data
        return response()->json([
            'message' => 'Machine soft deleted successfully',
            'data' => new MachineResource($machine),
        ]);
    }
}
