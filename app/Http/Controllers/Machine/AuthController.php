<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\AuthCredentialDto;
use App\DTOs\MachineLogDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Machine\AuthLoginRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Services\MachineLogService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(public AuthService $authService)
    {
        //
    }

    public function login(AuthLoginRequest $request)
    {
        $user = User::where('employee_number', $request->employee_number)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return ApiResponse::error(
                'Invalid employee number',
                [
                    'employee_number' => 'error.invalid_credential',
                ],
                401
            );
        }

        $credential = AuthCredentialDto::usingMachine(
            $user,
            $request->machine_code
        );

        $auth = AuthService::authenticateUseMachine($credential);

        if (!$auth->isSuccess()) {
            return ApiResponse::error(
                $auth->errorMessage,
                [
                    'auth' => 'error.unauthorized',
                ],
                403
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
                ],
                'machine_code' => $request->machine_code,
            ],
            'Successful'
        );
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        $machineCode = $token?->name ?? 'UNKNOWN';

        MachineLogService::addLog(
            MachineLogDto::logout($user, $machineCode)
        );

        $token?->delete();

        return ApiResponse::success(
            null,
            'Successful'
        );
    }
}
