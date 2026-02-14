<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\UserShiftDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\UserShift\CreateUpdateUserShiftRequest;
use App\Http\Requests\BackOffice\UserShift\UpdateUserShiftRequest;
use App\Http\Resources\BackOffice\UserShiftResource;
use App\Services\UserShiftService;
use Illuminate\Http\Request;

class UserShiftController extends Controller
{
    public function __construct(protected UserShiftService $userShiftService)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return UserShiftResource::collection($this->userShiftService->getAll());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUpdateUserShiftRequest $request)
    {
        $dto = UserShiftDto::fromRequest($request);
        $userShift = $this->userShiftService->create($dto);

        return new UserShiftResource($userShift);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new UserShiftResource($this->userShiftService->findById($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CreateUpdateUserShiftRequest $request, string $id)
    {
        $dto = UserShiftDto::fromRequest($request);
        $userShift = $this->userShiftService->update($id, $dto);

        return new UserShiftResource($userShift);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->userShiftService->delete($id);

        return response()->json([
            'message' => 'User shift deleted successfully'
        ]);
    }
}
