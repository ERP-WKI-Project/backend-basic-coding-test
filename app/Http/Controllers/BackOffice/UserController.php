<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\UserDto;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use App\Http\Requests\BackOffice\User\StoreUserRequest;
use App\Http\Requests\BackOffice\User\UpdateUserRequest;
use App\Http\Resources\BackOffice\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $users = $this->userService->getPaginatedUsers($filters, 10);
        return $this->successResponse(UserResource::collection($users), 'Users retrieved successfully');
    }

    public function store(StoreUserRequest $request)
    {
        try {
            $user = $this->userService->createUser(UserDto::fromRequest($request));
    
            return $this->successResponse(new UserResource($user), 'User created successfully', 201);
        } catch (\Throwable $th) {
            report($th);
            return $this->errorResponse('Failed to create user: ' . $th->getMessage(), 422);
        }
    }

    public function show(User $user)
    {
        return $this->successResponse(new UserResource($user), 'User retrieved successfully');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $user = $this->userService->updateUser($user, UserDto::fromRequest($request));

            return $this->successResponse(new UserResource($user), 'User updated successfully.');
        } catch (\Throwable $th) {
            report($th);
            return $this->errorResponse('Failed to update user: ' . $th->getMessage(), 422);
        }
    }

    public function destroy(User $user)
    {
        try {
            $this->userService->deleteUser($user);

            return $this->successResponse(null, 'User deleted successfully.');
        } catch (\Throwable $th) {
            report($th);
            return $this->errorResponse('Failed to delete user: ' . $th->getMessage(), 409);
        }
    }
}
