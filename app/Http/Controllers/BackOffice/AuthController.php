<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\AuthCredentialDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\AuthLoginRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(public AuthService $authService)
    {
        //
    }

    public function login(AuthLoginRequest $request)
    {
        $user = User::query()->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            abort(403, 'Email atau password salah.');
        }

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
