<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\AuthDto;
use App\Http\Business\BackOfficeAuth\BackOfficeAuth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected BackOfficeAuth $backOfficeAuth;

    public function __construct(BackOfficeAuth $backOfficeAuth)
    {
        $this->backOfficeAuth = $backOfficeAuth;
    }

    /**
     * Login user with email and password
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'nik' => 'required',
                'password' => 'required|string|min:6',
            ]);

            $result = $this->backOfficeAuth->login($validated['nik'], $validated['password']);
            if (!$result->isSuccess()) {
                return response()->json(AuthDto::failure($result->errorMessage));
            }

            return response()->json(AuthDto::success($result->token));
        } catch (ValidationException $e) {
            return response()->json(
                AuthDto::failure('Validation failed: ' .  $e->getMessage())
            );
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();


            if (!$user) {
                return response()->json(AuthDto::failure('User not authenticated', 401));
            }

            $isLoggedOut = $this->backOfficeAuth->logout($user);

            if (!$isLoggedOut) {
                return response()->json(AuthDto::failure('Logout failed', 500));
            }

            return response()->json(AuthDto::success('Logout successful'));
        } catch (\Exception $e) {
            return response()->json(AuthDto::failure('An error occurred during logout: ' . $e->getMessage(), 500));
        }
    }
}
