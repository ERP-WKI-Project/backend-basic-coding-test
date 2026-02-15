<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\AuthCredentialDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\BackOfficeLoginRequest;
use App\Services\AuthService;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $authService,
        private readonly UserService $userService,
    ) {}

    public function login(BackOfficeLoginRequest $request): JsonResponse
    {
        $user = $this->userService->findByEmployeeNumber($request->employee_number);

        if (! $user) {
            return $this->errorResponse(__('messages.auth_user_not_found'), 404);
        }

        $auth = AuthService::authenticateUseBackoffice(
            AuthCredentialDto::usingPassword($user, $request->password)
        );

        if (! $auth->isSuccess()) {
            return $this->errorResponse($auth->errorMessage, 401);
        }

        return $this->successResponse([
            'access_token' => $auth->token,
            'token_type' => 'Bearer',
        ], __('messages.auth_login_success'));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, __('messages.auth_logout_success'));
    }
}
