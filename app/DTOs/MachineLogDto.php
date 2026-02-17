<?php

namespace App\DTOs;

use App\Enums\MachineLog\SeverityEnum;

readonly class MachineLogDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public \App\Models\User $user,
        public string $machineCode,
        public \App\Enums\MachineLog\MachineLogEventEnum $event,
        public string $logMessage,
        public ?SeverityEnum $severity = null,
        public ?array $metadata = null,
    )
    {
        //
    }

    public static function fromAuth(AuthCredentialDto $credDto, AuthDto $authDto): self
    {
        $event = $authDto->isSuccess() ? \App\Enums\MachineLog\MachineLogEventEnum::LOGIN_SUCCESS : \App\Enums\MachineLog\MachineLogEventEnum::LOGIN_FAILED;

        return new self(
            user: $credDto->user,
            machineCode: $credDto->machineCode ?? 'unknown',
            event: $event,
            logMessage: $authDto->isSuccess() ? 'Login successful' : 'Login failed: ' . ($authDto->errorMessage ?? 'Unknown error'),
        );
    }

    public function toArray(): array
    {
        $data = [
            'user_id' => $this->user->id,
            'machine_code' => $this->machineCode,
            'event' => $this->event->value,
            'log_message' => $this->logMessage,
        ];

        if ($this->severity !== null) {
            $data['severity'] = $this->severity->value;
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }
}
