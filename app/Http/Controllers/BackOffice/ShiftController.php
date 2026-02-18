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
        private UserShiftService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get the 'per_page' query parameter from the request or default to 10
        $perPage = $request->get('per_page', 10);

        // Fetch a paginated list of user shifts using the service layer
        $userShifts = $this->service->list($perPage);

        // Return the list as a collection of resources for consistent API formatting
        return UserShiftResource::collection($userShifts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserShiftRequest $request)
    {
        // Validate the request and convert the input data into a Data Transfer Object (DTO)
        $dto = UserShiftDto::fromArray($request->validated());

        // Pass the DTO to the service layer to create a new user shift
        $userShift = $this->service->create($dto);

        // Return the created user shift as a resource with HTTP status 201 (Created)
        return (new UserShiftResource($userShift))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserShift $shift)
    {
        // Use the service layer to retrieve the full details of the user shift
        $shift = $this->service->find($shift);

        // Return the user shift details as a resource
        return new UserShiftResource($shift);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserShiftRequest $request, UserShift $shift)
    {
        // Validate the request and convert the input into a DTO
        $dto = UserShiftDto::fromArray($request->validated());

        // Update the user shift using the service layer and the provided DTO
        $shift = $this->service->update($shift, $dto);

        // Return the updated user shift as a resource
        return new UserShiftResource($shift);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserShift $shift)
    {
        // Delete the user shift using the service layer
        $this->service->delete($shift);

        // Return a JSON response indicating successful deletion and include the deleted shift as a resource
        return response()->json([
            'message' => 'User shift removed successfully.',
            'data' => new UserShiftResource($shift),
        ]);
    }
}
