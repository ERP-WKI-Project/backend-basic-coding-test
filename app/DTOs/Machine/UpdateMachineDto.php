<?php

namespace App\DTOs\Machine;

use App\Enums\MachineStatus;
use App\Http\Requests\BackOffice\UpdateMachineRequest;

readonly class UpdateMachineDto
{
    public function __construct(
        public ?string $code,
        public ?string $name,
        public ?MachineStatus $status,
        public bool $hasStatus = false,
    ) {}

    public static function fromRequest(UpdateMachineRequest $request): self
    {
        return new self(
            code: $request->validated('code'),
            name: $request->validated('name'),
            status: $request->has('status') ? MachineStatus::tryFrom($request->validated('status')) : null,
            hasStatus: $request->has('status'),
        );
    }

    public function toArray(): array
    {
        $data = array_filter([
            'code' => $this->code,
            'name' => $this->name,
        ], fn ($value) => ! is_null($value));

        if ($this->hasStatus) {
            $data['status'] = $this->status?->value;
        }

        return $data;
    }
}
