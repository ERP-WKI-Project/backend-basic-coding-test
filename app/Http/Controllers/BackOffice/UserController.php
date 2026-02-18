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
        // Get the 'per_page' query parameter or default to 10 items per page
        $perPage = $request->get('per_page', 10);

        // Get the 'page' query parameter or default to page 1
        $page = $request->get('page', 1);

        // Fetch a paginated list of users from the service layer
        $users = $this->service->list($perPage, $page);

        // Return the users as a collection of resources for consistent API formatting
        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        // Validate the request and convert the data into a Data Transfer Object (DTO)
        $dto = UserDto::fromArray($request->validated());

        // Create a new user using the service layer with the provided DTO
        $user = $this->service->create($dto);

        // Return the created user as a resource with HTTP status 201 (Created)
        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user)
    {
        // Retrieve the full details of the user via the service layer
        $user = $this->service->find($user);

        // Return the user details as a resource
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        // Validate the request and convert the data into a DTO
        $dto = UserDto::fromArray($request->validated());

        // Update the user using the service layer with the provided DTO
        $user = $this->service->update($user, $dto);

        // Return the updated user as a resource
        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        // Soft delete the user via the service layer
        $user = $this->service->delete($user);

        // Return a JSON response indicating successful soft deletion
        // Include the deleted user's data as a resource
        return response()->json([
            'message' => 'User soft deleted successfully',
            'data' => new UserResource($user),
        ]);
    }
}
