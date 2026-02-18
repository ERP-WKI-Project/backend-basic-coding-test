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

    /**
     * Machine Login
     *
     * Authenticate a machine user with PIN and machine code.
     *
     * @tag Machine Authentication
     * @unauthenticated
     */
    public function login(AuthLoginRequest $request)
    {
        $user = User::query()->where('employee_number', $request->pin)->first();
        abort_unless(!empty($user), 404, 'PIN tidak ditemukan.');
        $auth = $this->authService->authenticateUseMachine(AuthCredentialDto::usingMachine($user, $request->machine_code));
        abort_unless($auth->isSuccess(), 403, $auth->errorMessage);
        return response()->json(['access_token' => $auth->token, 'token_type' => 'Bearer']);
    }

    /**
     * Machine Logout
     *
     * Revoke the current machine access token.
     *
     * @tag Machine Authentication
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Berhasil logout.']);
    }
}
