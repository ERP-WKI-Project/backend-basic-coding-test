<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\MachineDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Machine\CreateMachineRequest;
use App\Http\Requests\BackOffice\Machine\UpdateMachineRequest;
use App\Http\Resources\BackOffice\MachineResource;
use App\Services\MachineService;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function __construct(protected MachineService $machineService)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(
            MachineResource::collection($this->machineService->getAll())
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateMachineRequest $request)
    {
        $dto = MachineDto::fromArray($request->validated());
        $user = $this->machineService->create($dto);

        return response()->json(
            new MachineResource($user),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json(
            new MachineResource($this->machineService->findById($id))
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMachineRequest $request, string $id)
    {
        $dto = MachineDto::fromArray($request->validated());
        $user = $this->machineService->update($id, $dto);

        return response()->json(new MachineResource($user));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->machineService->delete($id);

        return response()->json([
            'message' => 'Machine deleted successfully'
        ]);
    }
}
