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
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $machines = $this->service->list($perPage, $page);

        return response()->json($machines);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMachineRequest $request)
    {
        $dto = MachineDto::fromArray($request->validated());

        $machine = $this->service->create($dto);

        return (new MachineResource($machine))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Machine $machine)
    {
        $machine = $this->service->find($machine);

        return new MachineResource($machine);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMachineRequest $request, Machine $machine)
    {
        $dto = MachineDto::fromArray($request->validated());

        $machine = $this->service->update($machine, $dto);

        return new MachineResource($machine);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Machine $machine)
    {
        $machine = $this->service->delete($machine);

        return response()->json([
            'message' => 'Machine soft deleted successfully',
            'data' => new MachineResource($machine),
        ]);
    }
}
