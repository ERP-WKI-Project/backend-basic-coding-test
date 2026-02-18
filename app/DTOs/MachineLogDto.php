<?php

namespace App\DTOs;

readonly class MachineLogDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public \App\Models\User $user,
        public int $machineId,
        public \App\Enums\MachineLog\EventEnum $event,
        public string $logMessage,
        public ?array $metadata = null,
    ) {
        //
    }

    public static function fromAuth(AuthCredentialDto $credDto, AuthDto $authDto): self
    {
        $event = $authDto->isSuccess() ? \App\Enums\MachineLog\EventEnum::LOGIN_SUCCESS : \App\Enums\MachineLog\EventEnum::LOGIN_FAILED;

        return new self(
            user: $credDto->user,
            machineId: $credDto->machine->id ?? 0,
            event: $event,
            logMessage: $authDto->isSuccess() ? 'Login successful' : 'Login failed: '.($authDto->errorMessage ?? 'Unknown error'),
        );
    }
}
