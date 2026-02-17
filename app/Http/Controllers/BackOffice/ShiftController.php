<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Shift\StoreShiftRequest;
use App\Http\Requests\BackOffice\Shift\UpdateShiftRequest;
use App\Http\Resources\BackOffice\Shift\UserShiftResource;
use App\Models\UserShift;
use App\Services\ShiftService;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    protected ShiftService $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'date', 'machine_id', 'status']);
        $userShifts = $this->shiftService->getPaginatedUserShifts($filters, 10);

        return $this->successResponse(
            UserShiftResource::collection($userShifts),
            'User shifts retrieved successfully.'
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreShiftRequest $request)
    {
        try {
            $userShift = $this->shiftService->createShift($request->validated());

            $userShift->load(['user', 'shift', 'machine', 'createdBy']);

            return $this->successResponse(
                new UserShiftResource($userShift),
                'Shift successfully assigned to employee.',
                201
            );
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(UserShift $shift)
    {
        $shift->load(['user', 'shift', 'machine', 'createdBy']);

        return $this->successResponse(new UserShiftResource($shift), 'Shift retrieved successfully');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShiftRequest $request, UserShift $shift)
    {
        try {
            $updated = $this->shiftService->updateShift($shift, $request->validated());

            return $this->successResponse(
                new UserShiftResource($updated),
                'Shift assignment updated successfully.'
            );
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserShift $shift)
    {
        try {
            $this->shiftService->deleteShift($shift);

            return $this->successResponse(null, 'Shift deleted successfully');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
