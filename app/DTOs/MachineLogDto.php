<?php

namespace App\DTOs;

use App\Http\Requests\Machine\StoreLogEntryRequest;

readonly class MachineLogDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public \App\Models\User $user,
        public string $machineCode,
        public string $event,
        public string $logMessage,
    ) {}

    public static function fromAuth(AuthCredentialDto $credDto, AuthDto $authDto): self
    {
        $event = $authDto->isSuccess()
            ? \App\Enums\MachineLog\EventEnum::LOGIN_SUCCESS->value
            : \App\Enums\MachineLog\EventEnum::LOGIN_FAILED->value;

        return new self(
            user: $credDto->user,
            machineCode: $credDto->machineCode ?? 'unknown',
            event: $event,
            logMessage: $authDto->isSuccess() ? 'Login successful' : 'Login failed: ' . ($authDto->errorMessage ?? 'Unknown error'),
        );
    }

    public static function fromRequest(StoreLogEntryRequest $request): self
    {
        return new self(
            user: $request->user(),
            machineCode: $request->validated('machine_code'),
            event: $request->validated('event'),
            logMessage: $request->validated('log_message'),
        );
    }
}
