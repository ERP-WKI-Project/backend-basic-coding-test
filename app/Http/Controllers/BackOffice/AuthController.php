<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\LoginRequest;
use App\Services\BackOffice\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(public AuthService $authService)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login(
            $request->validated('email'),
            $request->validated('password')
        );

        if (!$user) {
            return $this->unauthorized('Email atau password salah.');
        }

        $token = $this->authService->createToken($user);

        return $this->success(
            [
                'user' => [
                    'id' => $user->id,
                    'employee_number' => $user->employee_number,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            'Login berhasil.'
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authService->revokeCurrentToken($user);

        return $this->success(message: 'Logout berhasil.');
    }
}