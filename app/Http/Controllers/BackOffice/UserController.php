<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\UserDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\User\CreateUserRequest;
use App\Http\Requests\BackOffice\User\UpdateUserRequest;
use App\Http\Requests\BackOffice\User\UserIndexRequest;
use App\Http\Resources\BackOffice\UserResource;
use App\Models\User;
use App\Services\BackOffice\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(public UserService $userService) {}

    public function index(UserIndexRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $limit = (int) ($validated['limit'] ?? 10);
        $search = $validated['search'] ?? null;

        $users = $this->userService->getPaginatedUsers($limit, $search);

        return UserResource::collection($users);
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $dto = UserDto::fromRequest($request);
        $user = $this->userService->createUser($dto);

        return UserResource::make($user)->response()->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return UserResource::make($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $dto = UserDto::fromRequest($request);
        $updatedUser = $this->userService->updateUser($user, $dto);

        return UserResource::make($updatedUser);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->deleteUser($user);

        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    public function restore(int $id): JsonResponse
    {
        $user = $this->userService->restoreUser($id);

        if (! $user) {
            return response()->json(['message' => 'User tidak ditemukan.'], 404);
        }

        return response()->json(['message' => 'User berhasil dipulihkan.', 'data' => new \App\Http\Resources\BackOffice\UserResource($user)]);
    }
}
