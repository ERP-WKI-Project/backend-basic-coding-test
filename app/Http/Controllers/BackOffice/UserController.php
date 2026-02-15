<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\User\CreateUserDto;
use App\DTOs\User\UpdateUserDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\StoreUserRequest;
use App\Http\Requests\BackOffice\UpdateUserRequest;
use App\Http\Resources\BackOffice\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $users = $this->userService->getAll(
            search: $request->query('search'),
        );

        return $this->collectionResponse(
            UserResource::collection($users),
            __('messages.users_retrieved'),
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create(CreateUserDto::fromRequest($request));

        return $this->resourceResponse(
            new UserResource($user),
            __('messages.user_created'),
            201,
        );
    }

    public function show(User $user): JsonResponse
    {
        return $this->resourceResponse(
            new UserResource($user),
            __('messages.user_retrieved'),
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->update($user, UpdateUserDto::fromRequest($request));

        return $this->resourceResponse(
            new UserResource($user),
            __('messages.user_updated'),
        );
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return $this->successResponse(null, __('messages.user_deleted'));
    }
}
