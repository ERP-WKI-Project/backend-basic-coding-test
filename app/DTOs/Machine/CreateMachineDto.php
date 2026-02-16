<?php

namespace App\DTOs\Machine;

use App\Enums\MachineStatus;
use App\Http\Requests\BackOffice\StoreMachineRequest;

readonly class CreateMachineDto
{
    public function __construct(
        public string $code,
        public string $name,
        public MachineStatus $status,
    ) {}

    public static function fromRequest(StoreMachineRequest $request): self
    {
        return new self(
            code: $request->validated('code'),
            name: $request->validated('name'),
            status: MachineStatus::tryFrom($request->validated('status')) ?? MachineStatus::ACTIVE,
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status->value,
        ];
    }
}
