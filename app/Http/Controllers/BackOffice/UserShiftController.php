<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\UserShift\CreateUserShiftDto;
use App\DTOs\UserShift\UpdateUserShiftDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\StoreUserShiftRequest;
use App\Http\Requests\BackOffice\UpdateUserShiftRequest;
use App\Http\Requests\BackOffice\UserShiftFilterRequest;
use App\Http\Resources\BackOffice\UserShiftResource;
use App\Models\UserShift;
use App\Services\UserShiftService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserShiftController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserShiftService $userShiftService
    ) {}

    public function index(UserShiftFilterRequest $request): JsonResponse
    {
        $userShifts = $this->userShiftService->getAll(
            filters: $request->validated(),
            search: $request->input('search')
        );

        return $this->collectionResponse(
            UserShiftResource::collection($userShifts),
            __('messages.user_shift_listed')
        );
    }

    public function store(StoreUserShiftRequest $request): JsonResponse
    {
        $userShift = $this->userShiftService->create(
            CreateUserShiftDto::fromRequest($request)
        );

        return $this->resourceResponse(
            new UserShiftResource($userShift),
            __('messages.user_shift_created'),
            201
        );
    }

    public function show(UserShift $userShift): JsonResponse
    {
        return $this->resourceResponse(
            new UserShiftResource($userShift),
            __('messages.user_shift_retrieved')
        );
    }

    public function update(UpdateUserShiftRequest $request, UserShift $userShift): JsonResponse
    {
        $userShift = $this->userShiftService->update(
            $userShift,
            UpdateUserShiftDto::fromRequest($request)
        );

        return $this->resourceResponse(
            new UserShiftResource($userShift),
            __('messages.user_shift_updated')
        );
    }

    public function destroy(UserShift $userShift): JsonResponse
    {
        if ($userShift->shift_date->isBefore(today())) {
            return $this->errorResponse(
                __('messages.cannot_delete_past_shift'),
                422
            );
        }

        $this->userShiftService->delete($userShift);

        return $this->successResponse(null, __('messages.user_shift_deleted'));
    }
}
