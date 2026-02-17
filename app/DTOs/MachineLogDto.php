<?php

namespace App\DTOs;

use App\Enums\MachineLog\EventEnum;
use App\Http\Requests\Machine\LogEntry\StoreLogEntryRequest;
use App\Models\Machine;
use Illuminate\Support\Facades\Request;

readonly class MachineLogDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public \App\Models\User $user,
        public int $machineId,
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

        $machine = Machine::where('code', $credDto->machineCode)->first();
        
        return new self(
            user: $credDto->user,
            machineId: $machine->id,
            machineCode: $credDto->machineCode ?? 'unknown',
            event: $event,
            logMessage: $authDto->isSuccess() ? 'Login successful' : 'Login failed: ' . ($authDto->errorMessage ?? 'Unknown error'),
        );
    }

    public static function fromRequest(StoreLogEntryRequest $request): self
    {
        $machine = Machine::where('ulid', $request->validated('machine_id'))->firstOrFail();

        return new self(
            user: $request->user(),
            machineId: $machine->id,
            machineCode: $machine->code,
            event: EventEnum::from($request->validated('event')),
            logMessage: $request->validated('log_message'),
        );
    }
}
