<?php

namespace App\DTOs;

use App\Enums\MachineLog\EventEnum;
use App\Http\Requests\Machine\LogEntry\StoreLogEntryRequest;
use App\Models\Machine;
use App\Models\UserShift;
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
        public int $userShiftId,
        public \App\Enums\MachineLog\EventEnum $event,
        public string $logMessage,
    )
    {
        //
    }

    public static function fromAuth(AuthCredentialDto $credDto, AuthDto $authDto, UserShift $userShift): self
    {
        $event = $authDto->isSuccess() ? \App\Enums\MachineLog\EventEnum::LOGIN_SUCCESS : \App\Enums\MachineLog\EventEnum::LOGIN_FAILED;

        $machine = Machine::where('code', $credDto->machineCode)->first();
        
        return new self(
            user: $credDto->user,
            machineId: $machine->id,
            machineCode: $credDto->machineCode ?? 'unknown',
            userShiftId: $userShift->id,
            event: $event,
            logMessage: $authDto->isSuccess() ? 'Login successful' : 'Login failed: ' . ($authDto->errorMessage ?? 'Unknown error'),
        );
    }

    public static function fromRequest(StoreLogEntryRequest $request, Machine $machine, UserShift $userShift): self
    {
        return new self(
            user: $request->user(),
            machineId: $machine->id,
            machineCode: $machine->code,
            userShiftId: $userShift->id,
            event: EventEnum::from($request->validated('event')),
            logMessage: $request->validated('log_message'),
        );
    }
}
