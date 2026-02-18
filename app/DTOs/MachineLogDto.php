<?php

namespace App\DTOs;

use App\Models\User;

readonly class MachineLogDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public \App\Models\User $user,
        public string $machineCode,
        public \App\Enums\MachineLog\EventEnum $event,
        public string $logMessage,
    )
    {
        //
    }

    public static function fromAuth(AuthCredentialDto $credDto, AuthDto $authDto): self
    {
        $event = $authDto->isSuccess() ? \App\Enums\MachineLog\EventEnum::LOGIN_SUCCESS : \App\Enums\MachineLog\EventEnum::LOGIN_FAILED;

        return new self(
            user: $credDto->user,
            machineCode: $credDto->machineCode ?? 'unknown',
            event: $event,
            logMessage: $authDto->isSuccess() ? 'Login successful' : 'Login failed: ' . ($authDto->errorMessage ?? 'Unknown error'),
        );
    }

    public static function logout(User $user, string $machineCode): self
    {
        return new self(
            user: $user,
            machineCode: $machineCode,
            event: \App\Enums\MachineLog\EventEnum::LOGOUT,
            logMessage: 'Logout successful'
        );
    }
}
