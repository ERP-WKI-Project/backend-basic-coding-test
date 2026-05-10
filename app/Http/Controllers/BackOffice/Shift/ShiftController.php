<?php

namespace App\Http\Controllers\BackOffice\Shift;

use App\DTOs\ShiftDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Shift\CreateShiftRequest;
use App\Http\Requests\BackOffice\Shift\ShiftIndexRequest;
use App\Http\Requests\BackOffice\Shift\UpdateShiftRequest;
use App\Http\Resources\BackOffice\ShiftResource;
use App\Models\Shift;
use App\Services\BackOffice\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShiftController extends Controller
{
    public function __construct(public ShiftService $shiftService) {}

    public function index(ShiftIndexRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $limit = (int) ($validated['limit'] ?? 10);
        $search = $validated['search'] ?? null;
        $dayOfWeek = isset($validated['day_of_week']) ? (int) $validated['day_of_week'] : null;

        $shifts = $this->shiftService->getPaginatedShifts($limit, $search, $dayOfWeek);

        return ShiftResource::collection($shifts);
    }

    public function store(CreateShiftRequest $request): JsonResponse
    {
        $dto = ShiftDto::fromArray($request->validated());
        $shift = $this->shiftService->createShift($dto);

        return $this->created(new ShiftResource($shift), 'Shift berhasil dibuat.');
    }

    public function show(Shift $shift): ShiftResource
    {
        return ShiftResource::make($shift);
    }

    public function update(UpdateShiftRequest $request, Shift $shift): ShiftResource
    {
        $dto = ShiftDto::fromArray($request->validated());
        $updatedShift = $this->shiftService->updateShift($shift, $dto);

        return ShiftResource::make($updatedShift);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        $this->shiftService->deleteShift($shift);

        return $this->success(message: 'Shift berhasil dihapus.');
    }
}