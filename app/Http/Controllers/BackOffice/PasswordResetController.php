<?php

namespace App\Http\Controllers\BackOffice;

use App\DTOs\BaseResponseDto;
use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    private PasswordResetService $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Generate reset token by nik (employee_number)
     * POST /api/backoffice/v1/password-reset
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nik' => 'required|string',
            ]);

            $result = $this->passwordResetService->generateResetToken($validated['nik']);

            if (isset($result['error'])) {
                return response()->json(BaseResponseDto::error($result['error']), $result['status_code'] ?? 400);
            }

            return response()->json(BaseResponseDto::success('Reset token generated', ['token' => $result['token']]), 200);
        } catch (ValidationException $e) {
            return response()->json(BaseResponseDto::error('Validation failed', $e->errors()), 422);
        } catch (\Exception $e) {
            return response()->json(BaseResponseDto::error('Failed to generate reset token', [], $e->getMessage()), 500);
        }
    }

    /**
     * Verify token and reset password
     * POST /api/backoffice/v1/password-reset/verify/{token}
     */
    public function verify(Request $request, string $token)
    {
        try {
            $validated = $request->validate([
                'password' => 'required|string|min:6',
            ]);

            $result = $this->passwordResetService->verifyTokenAndReset($token, $validated['password']);

            if (isset($result['error'])) {
                return response()->json(BaseResponseDto::error($result['error']), $result['status_code'] ?? 400);
            }

            return response()->json(BaseResponseDto::success('Password reset successful'), 200);
        } catch (ValidationException $e) {
            return response()->json(BaseResponseDto::error('Validation failed', $e->errors()), 422);
        } catch (\Exception $e) {
            return response()->json(BaseResponseDto::error('Failed to reset password', [], $e->getMessage()), 500);
        }
    }
}
