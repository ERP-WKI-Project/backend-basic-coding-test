<?php

namespace App\Services;

use App\Models\PasswordResetToken;
use App\Models\User;

class PasswordResetService
{
    /**
     * Generate a password reset token for a user identified by nik (employee number)
     * Returns array with token on success, or error array on failure
     */
    public function generateResetToken(string $nik): array
    {
        $user = User::getByNik($nik);
        if (!$user) {
            return ['error' => 'User not found', 'status_code' => 404];
        }

        $token = bin2hex(random_bytes(32));

        PasswordResetToken::createResetToken($user->email, $token);

        // In production, you would email the token. For now return it in response.
        return ['token' => $token, 'status_code' => 200];
    }

    /**
     * Verify token and reset password
     * Returns ['success'=>true] or error array
     */
    public function verifyTokenAndReset(string $token, string $newPassword): array
    {
        $record = PasswordResetToken::getByToken($token);
        if (!$record) {
            return ['error' => 'Token not found', 'status_code' => 404];
        }

        // Check token validity (default 60 minutes)
        if (!PasswordResetToken::isValidToken($token, 60)) {
            // delete expired token
            PasswordResetToken::deleteByEmail($record->email);
            return ['error' => 'Token expired', 'status_code' => 400];
        }

        $email = $record->email;

        $updatedUser = User::updatePasswordByEmail($email, $newPassword);
        if (!$updatedUser) {
            return ['error' => 'User not found for email', 'status_code' => 404];
        }

        // remove token after successful reset
        PasswordResetToken::deleteByEmail($email);

        return ['success' => true, 'status_code' => 200];
    }
}
