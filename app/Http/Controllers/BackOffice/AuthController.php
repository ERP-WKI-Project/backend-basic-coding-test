<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\AuthCredentialDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(public AuthService $authService)
    {
        //
    }

    public function login(LoginRequest $request)
    {
        $user = User::query()->where('employee_number', $request->pin)->first();
        abort_unless($user, 404, 'PIN tidak ditemukan.');
        $auth = $this->authService->authenticateBackOffice(AuthCredentialDto::usingPassword($user, $request->password));
        abort_unless($auth->isSuccess(), 403, $auth->errorMessage);
        return response()->json(['access_token' => $auth->token, 'token_type' => 'Bearer']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Berhasil logout.']);
    }
}
