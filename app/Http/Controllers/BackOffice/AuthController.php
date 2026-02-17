<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\AuthCredentialDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\LoginRequest;
use App\Http\Resources\BackOffice\AuthResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): AuthResource|JsonResponse
    {
        $validated = $request->validated();

        // Find user by employee_number
        $user = User::where('employee_number', $validated['employee_number'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Create DTO and authenticate
        $dto = AuthCredentialDto::usingPassword($user, $validated['password']);
        $auth = AuthService::authenticateBackOffice($dto);

        if (!$auth->isSuccess()) {
            return response()->json([
                'message' => $auth->errorMessage
            ], 401);
        }

        return new AuthResource([
            'token' => $auth->token,
            'user' => $user
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Revoke current access token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
