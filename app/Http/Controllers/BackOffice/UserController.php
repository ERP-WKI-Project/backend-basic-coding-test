<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\UserDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\StoreUserRequest;
use App\Http\Requests\BackOffice\UpdateUserRequest;
use App\Http\Resources\BackOffice\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $users = UserService::getAllUsers($perPage);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $dto = UserDto::fromRequest($request->validated());
        $user = UserService::createUser($dto);

        return new UserResource($user);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $dto = UserDto::fromRequest($request->validated());
        $updatedUser = UserService::updateUser($user, $dto);

        return new UserResource($updatedUser);
    }

    public function destroy(User $user): JsonResponse
    {
        UserService::deleteUser($user);

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}
