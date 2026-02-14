<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\UserDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\User\CreateUserRequest;
use App\Http\Requests\BackOffice\User\UpdateUserRequest;
use App\Http\Resources\BackOffice\UserResource;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(protected UserService $userService)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(
            UserResource::collection($this->userService->getAll())
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUserRequest $request)
    {
        $dto = UserDTO::fromArray($request->validated());
        $user = $this->userService->create($dto);

        return response()->json(
            new UserResource($user),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        return response()->json(
            new UserResource($this->userService->findById($id))
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, int $id)
    {
        $dto = UserDTO::fromArray($request->validated());
        $user = $this->userService->update($id, $dto);

        return response()->json(new UserResource($user));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $this->userService->delete($id);

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}
