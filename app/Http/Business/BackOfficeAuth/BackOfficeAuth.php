<?php

namespace App\Http\Business\BackOfficeAuth;

use App\DTOs\AuthDto;
use App\Models\User;

class BackOfficeAuth
{
    /**
     * Authenticate user with nik and password
     */
    public function login(string $nik, string $password): AuthDto
    {
        $user = User::getByNikAndPassword($nik, $password);

        if (!$user) {
            return AuthDto::failure('Invalid nik or password');
        }

        $tokenData = $user->createPersonalAccessToken('backoffice-token');

        return AuthDto::success($tokenData['token']);
    }

    /**
     * Logout user by revoking all tokens
     */
    public function logout(User $user): bool
    {
        try {
            $user->revokeAllTokens();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
