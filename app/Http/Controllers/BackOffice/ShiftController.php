<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\UserShiftDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\StoreUserShiftRequest;
use App\Http\Requests\BackOffice\UpdateUserShiftRequest;
use App\Http\Resources\BackOffice\UserShiftResource;
use App\Models\UserShift;
use App\Services\UserShiftService;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(
        private UserShiftService $userShiftService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $userShifts = $this->userShiftService->list($perPage);

        return UserShiftResource::collection($userShifts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserShiftRequest $request)
    {
        $dto = UserShiftDto::fromArray($request->validated());

        $userShift = $this->userShiftService->create($dto);

        return (new UserShiftResource($userShift))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserShift $shift)
    {
        $shift = $this->userShiftService->find($shift);

        return new UserShiftResource($shift);
    
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserShiftRequest $request, UserShift $shift)
    {
        $dto = UserShiftDto::fromArray($request->validated());

        $shift = $this->userShiftService->update($shift, $dto);

        return new UserShiftResource($shift);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserShift $shift)
    {
        $this->userShiftService->delete($shift);

        return response()->json([
            'message' => 'User shift removed successfully.',
            'data' => new UserShiftResource($shift),
        ]);
    }
}
