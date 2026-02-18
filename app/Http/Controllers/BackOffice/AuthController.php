<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\AuthCredentialDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\AuthLoginRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(AuthLoginRequest $request)
    {
        // Login logic for BackOffice users
        $user = User::where('employee_number', $request->employee_number)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return ApiResponse::error(
                'Invalid employee number or password',
                [
                    'employee' => 'error.invalid_credential',
                ],
                401
            );
        }

        $credential = AuthCredentialDto::usingPassword(
            $user,
            $request->password
        );

        $auth = AuthService::authenticateUsePassword($credential);
        if (!$auth->isSuccess()) {
            return ApiResponse::error(
                'Invalid employee number or password',
                [
                    'employee' => 'error.invalid_credential',
                ],
                401
            );
        }

        return ApiResponse::success(
            [
                'token' => $auth->token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'employee_number' => $user->employee_number,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
            'Successful'
        );
    }

    public function logout(Request $request)
    {
        // Logout logic for BackOffice users
        AuthService::logout($request->user());

        return ApiResponse::success(
            null,
            'Successful'
        );
    }
}
