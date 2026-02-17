<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\ShiftDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Http\Resources\BackOffice\ShiftResource;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShiftController extends Controller
{
    public function __construct(
        private readonly ShiftService $shiftService
    ) {}

    /**
     * Display a listing of shifts.
     */
    public function index(): AnonymousResourceCollection
    {
        $shifts = $this->shiftService->getAllShifts();
        return ShiftResource::collection($shifts);
    }

    /**
     * Store a newly created shift.
     */
    public function store(StoreShiftRequest $request): ShiftResource
    {
        $dto = ShiftDto::fromRequest($request->validated());
        $shift = $this->shiftService->createShift($dto);

        return new ShiftResource($shift);
    }

    /**
     * Display the specified shift.
     */
    public function show(int $id): ShiftResource
    {
        $shift = $this->shiftService->getShiftById($id);
        return new ShiftResource($shift);
    }

    /**
     * Update the specified shift.
     */
    public function update(UpdateShiftRequest $request, int $id): ShiftResource
    {
        $dto = ShiftDto::fromRequest(array_merge(
            $this->shiftService->getShiftById($id)->toArray(),
            $request->validated()
        ));

        $shift = $this->shiftService->updateShift($id, $dto);

        return new ShiftResource($shift);
    }

    /**
     * Remove the specified shift.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->shiftService->deleteShift($id);

        return response()->json(['message' => 'Shift deleted successfully'], 200);
    }
}