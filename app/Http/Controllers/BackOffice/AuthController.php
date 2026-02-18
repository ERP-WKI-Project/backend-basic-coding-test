<?php

namespace App\Http\Controllers\BackOffice;

use App\Enums\SystemAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\AuthLoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(AuthLoginRequest $request)
    {
        $user = User::query()->where('email', $request->email)->first();
        
        abort_unless($user, 404, 'Email tidak ditemukan.');
        abort_unless(Hash::check($request->password, $user->password), 401, 'Password salah.');
        
        $token = $user->createToken('auth_token', [SystemAbility::BACKOFFICE->value])->plainTextToken;
        
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_number' => $user->employee_number,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Berhasil logout.']);
    }
}
