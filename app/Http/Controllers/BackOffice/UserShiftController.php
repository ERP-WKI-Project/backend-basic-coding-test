<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\UserShiftDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\IndexUserShiftRequest;
use App\Http\Requests\BackOffice\StoreUserShiftRequest;
use App\Http\Requests\BackOffice\UpdateUserShiftRequest;
use App\Http\Resources\BackOffice\UserShiftResource;
use App\Models\UserShift;
use App\Services\UserShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserShiftController extends Controller
{
    /**
     * Get all user shifts
     *
     * Retrieve a paginated list of user shift assignments. Supports filtering by user, shift date, and machine.
     *
     * @tag User Shifts
     */
    public function index(IndexUserShiftRequest $request): AnonymousResourceCollection
    {
        $shifts = UserShiftService::getAllUserShifts(
            $request->query('limit', 15),
        );

        return UserShiftResource::collection($shifts);
    }

    /**
     * Assign user to shift
     *
     * Create a new shift assignment for a user with validation to prevent conflicts.
     *
     * @tag User Shifts
     * @throws \Throwable
     */
    public function store(StoreUserShiftRequest $request): JsonResponse
    {
        $dto = UserShiftDto::fromRequest($request->validated());
        $userShift = UserShiftService::assignUserShift($dto);

        return (new UserShiftResource($userShift))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get shift assignment details
     *
     * Retrieve details of a specific shift assignment by ID.
     *
     * @tag User Shifts
     */
    public function show(UserShift $userShift): UserShiftResource
    {
        $userShift->load(['user', 'shift', 'machine']);
        return new UserShiftResource($userShift);
    }

    /**
     * Update shift assignment
     *
     * Update an existing shift assignment with conflict validation.
     *
     * @tag User Shifts
     * @throws \Throwable
     */
    public function update(UpdateUserShiftRequest $request, UserShift $userShift): UserShiftResource
    {
        $dto = UserShiftDto::fromRequest($request->validated());
        $updatedShift = UserShiftService::updateUserShift($userShift, $dto);

        return new UserShiftResource($updatedShift);
    }

    /**
     * Delete shift assignment
     *
     * Remove a shift assignment from the system.
     *
     * @tag User Shifts
     */
    public function destroy(UserShift $userShift): JsonResponse
    {
        UserShiftService::deleteUserShift($userShift);

        return response()->json([
            'message' => 'Shift assignment deleted successfully'
        ]);
    }
}

