<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ShiftService;
use App\DTOs\CreateShiftDto;
use App\DTOs\UpdateShiftDto;
use App\Http\Requests\BackOffice\CreateShiftRequest;
use App\Http\Requests\BackOffice\UpdateShiftRequest;

class ShiftController extends Controller
{
    public function __construct(
        private ShiftService $shiftService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = $this->shiftService->getAllShift();

        return response()->json([
            'message' => 'Succesfull : Shift Index',
            'data' => $user,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['message' => 'Succesfull : Shift Create']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateShiftRequest $request)
    {
        $dto = CreateShiftDto::fromRequest($request);

        $user = $this->shiftService->createShift($dto);

        return response()->json($user, 201);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = $this->shiftService->getShift($id);

        return response()->json(['message' => 'Succesfull : Shift Show', 'data' => $user]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return response()->json(['message' => 'Succesfull : Shift Edit']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShiftRequest $request, string $id)
    {
        $dto = UpdateShiftDto::fromRequest($request);

        $user = $this->shiftService->updateShift($dto, $id);

        return response()->json(['message' => 'Succesfull : Shift Update', 'data' => $user]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = $this->shiftService->deleteShift($id);

        return response()->json(['message' => 'Succesfull : Shift Delete']);
    }
}
