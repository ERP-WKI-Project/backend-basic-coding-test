<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\BaseResponseDto;
use App\DTOs\UserDto;
use App\Http\Business\User\UserService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_number' => 'required|string|size:6|unique:users',
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:users',
                'password' => 'string|min:6',
            ]);

            $userDto = $this->userService->createUser($validated);
            $response = BaseResponseDto::success('User created successfully', $userDto->toArray());

            return response()->json($response->toArray(), 201);
        } catch (ValidationException $e) {
            $response = BaseResponseDto::failure('Validation failed', $e->errors());
            return response()->json($response->toArray(), 422);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while creating user');
            return response()->json($response->toArray(), 500);
        }
    }

    public function show(string $nik)
    {
        try {
            $userDto = $this->userService->getUserByNik($nik);

            if (!$userDto) {
                $response = BaseResponseDto::failure('User not found');
                return response()->json($response->toArray(), 404);
            }

            $response = BaseResponseDto::success('User retrieved successfully', $userDto->toArray());
            return response()->json($response->toArray(), 200);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while retrieving user');
            return response()->json($response->toArray(), 500);
        }
    }

    public function update(Request $request, string $nik)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $nik . ',employee_number',
                'password' => 'sometimes|string|min:6',
            ]);

            $userDto = $this->userService->updateUserByNik($nik, $validated);

            if (!$userDto) {
                $response = BaseResponseDto::failure('User not found');
                return response()->json($response->toArray(), 404);
            }

            $response = BaseResponseDto::success('User updated successfully', $userDto->toArray());
            return response()->json($response->toArray(), 200);
        } catch (ValidationException $e) {
            $response = BaseResponseDto::failure('Validation failed', $e->errors());
            return response()->json($response->toArray(), 422);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while updating user');
            return response()->json($response->toArray(), 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);
            $page = $request->query('page', 1);

            $perPage = max(1, min((int)$perPage, 100));
            $page = max(1, (int)$page);

            $data = $this->userService->getUserListFormatted($perPage, $page);

            $response = BaseResponseDto::success('Users retrieved successfully', $data);
            return response()->json($response->toArray(), 200);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while retrieving users');
            return response()->json($response->toArray(), 500);
        }
    }

    public function destroy(string $nik)
    {
        try {
            $deleted = $this->userService->deleteUserByNik($nik);

            if (!$deleted) {
                $response = BaseResponseDto::failure('User not found');
                return response()->json($response->toArray(), 404);
            }

            $response = BaseResponseDto::success('User deleted successfully');
            return response()->json($response->toArray(), 200);
        } catch (\Exception $e) {
            $response = BaseResponseDto::failure('An error occurred while deleting user');
            return response()->json($response->toArray(), 500);
        }
    }
}
