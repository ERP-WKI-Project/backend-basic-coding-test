<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\DTOs\CreateUserDto;
use App\DTOs\UpdateUserDto;
use App\Http\Requests\BackOffice\CreateUserRequest;
use App\Http\Requests\BackOffice\UpdateUserRequest;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->userService->getAllUser();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['message' => 'Succesfull : User Create']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUserRequest $request)
    {
        $dto = CreateUserDto::fromRequest($request);

        $user = $this->userService->createUser($dto);

        return response()->json($user, 201);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = $this->userService->getUser($id);

        return response()->json(['message' => 'Succesfull : User Show', 'data' => $user]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return response()->json(['message' => 'Succesfull : User Edit']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        $dto = UpdateUserDto::fromRequest($request);

        $user = $this->userService->updateUser($dto, $id);

        return response()->json(['message' => 'Succesfull : User Update', 'data' => $user]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = $this->userService->deleteUser($id);

        return response()->json(['message' => 'Succesfull : User Delete']);
    }
}
