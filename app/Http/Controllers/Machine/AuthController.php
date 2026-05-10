<?php

namespace App\Http\Controllers\Machine;

use App\DTOs\AuthCredentialDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Machine\AuthLoginRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(public AuthService $authService)
    {
        //
    }

    public function login(AuthLoginRequest $request)
    {
        $user = User::query()->where('employee_number', $request->pin)->first();
        abort_unless($user, 404, 'PIN tidak ditemukan.');
        $auth = $this->authService->authenticateUseMachine(AuthCredentialDto::usingMachine($user, $request->machine_code));
        abort_unless($auth->isSuccess(), 403, $auth->errorMessage);
        return $this->success([
            'access_token' => $auth->token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(message: 'Berhasil logout.');
    }
}
