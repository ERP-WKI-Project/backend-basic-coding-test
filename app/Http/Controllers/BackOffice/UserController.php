<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\UserDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\StoreUserRequest;
use App\Http\Requests\BackOffice\UpdateUserRequest;
use App\Http\Resources\BackOffice\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $service
    ) {}

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $users = $this->service->list($perPage);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        $dto = UserDto::fromArray($request->validated());

        $user = $this->service->create($dto);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user)
    {
        $user = $this->service->find($user);

        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $dto = UserDto::fromArray($request->validated());

        $user = $this->service->update($user, $dto);

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user = $this->service->delete($user);

        return response()->json([
            'message' => 'User soft deleted successfully',
            'data' => new UserResource($user),
        ]);
    }
}
