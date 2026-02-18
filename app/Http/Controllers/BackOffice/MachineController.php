<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MachineService;
use App\DTOs\CreateMachineDto;
use App\DTOs\UpdateMachineDto;
use App\Http\Requests\BackOffice\CreateMachineRequest;
use App\Http\Requests\BackOffice\UpdateMachineRequest;

class MachineController extends Controller
{
    public function __construct(
        private MachineService $MachineService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->MachineService->getAllMachine();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['message' => 'Succesfull : Machine Create']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateMachineRequest $request)
    {
        $dto = CreateMachineDto::fromRequest($request);

        $Machine = $this->MachineService->createMachine($dto);

        return response()->json($Machine, 201);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $Machine = $this->MachineService->getMachine($id);

        return response()->json(['message' => 'Succesfull : Machine Show', 'data' => $Machine]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return response()->json(['message' => 'Succesfull : Machine Edit']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMachineRequest $request, string $id)
    {
        $dto = UpdateMachineDto::fromRequest($request);

        $Machine = $this->MachineService->updateMachine($dto, $id);

        return response()->json(['message' => 'Succesfull : Machine Update', 'data' => $Machine]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $Machine = $this->MachineService->deleteMachine($id);

        return response()->json(['message' => 'Succesfull : Machine Delete']);
    }
}
