<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\UserDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\IndexUserRequest;
use App\Http\Requests\BackOffice\StoreUserRequest;
use App\Http\Requests\BackOffice\UpdateUserRequest;
use App\Http\Resources\BackOffice\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    /**
     * Get all users
     *
     * Retrieve a paginated list of users. Supports filtering by employee number, name, email, and searching.
     *
     * @tag Users
     */
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $users = UserService::getAllUsers($request->query('limit', 15), $request->query('q'));

        return UserResource::collection($users);
    }

    /**
     * Create a new user
     *
     * Create a new user with auto-generated employee number.
     *
     * @tag Users
     */
    public function store(StoreUserRequest $request): UserResource
    {
        $dto = UserDto::fromRequest($request->validated());
        $user = UserService::createUser($dto);

        return new UserResource($user);
    }

    /**
     * Get user details
     *
     * Retrieve details of a specific user by ID.
     *
     * @tag Users
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    /**
     * Update user
     *
     * Update an existing user's information.
     *
     * @tag Users
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $dto = UserDto::fromRequest($request->validated());
        $updatedUser = UserService::updateUser($user, $dto);

        return new UserResource($updatedUser);
    }

    /**
     * Delete user
     *
     * Soft delete a user from the system.
     *
     * @tag Users
     */
    public function destroy(User $user): JsonResponse
    {
        UserService::deleteUser($user);

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}
