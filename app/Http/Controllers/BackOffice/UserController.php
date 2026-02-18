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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(): AnonymousResourceCollection
    {
        $users = User::query()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return UserResource::collection($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $dto = UserDto::fromRequest($request->validated());
        $user = $this->userService->createUser($dto);

        return response()->json([
            'message' => 'User berhasil dibuat',
            'data' => new UserResource($user)
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        // Merge existing data with request data for partial updates
        $data = array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'employee_number' => $user->employee_number,
        ], $request->validated());
        
        $dto = UserDto::fromRequest($data);
        $user = $this->userService->updateUser($user, $dto);

        return response()->json([
            'message' => 'User berhasil diupdate',
            'data' => new UserResource($user)
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->userService->deleteUser($user);

        return response()->json([
            'message' => 'User berhasil dihapus'
        ]);
    }
}
