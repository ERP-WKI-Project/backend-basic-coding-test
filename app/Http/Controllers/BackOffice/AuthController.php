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
        $user = User::where(function ($query) use ($request) {
                $query->where('email', $request->username)
                    ->orWhere('employee_number', $request->username);
            })
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Invalid credentials. Please check your email/employee number and password.', 401);
        }

        $auth = $this->authService->authenticateBackOffice(AuthCredentialDto::usingPassword($user, $request->password));
        if (!$auth->isSuccess()) {
            return $this->errorResponse($auth->errorMessage, 403);
        }

        return response()->json(['access_token' => $auth->token, 'token_type' => 'Bearer']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Berhasil logout.']);
    }
}
