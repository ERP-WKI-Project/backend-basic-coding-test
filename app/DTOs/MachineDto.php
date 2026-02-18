<?php

namespace App\DTOs;

use App\Http\Requests\BackOffice\Machine\StoreMachineRequest;
use App\Http\Requests\BackOffice\Machine\UpdateMachineRequest;

readonly class MachineDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description = null,
        public readonly ?string $status = null,
    ) {}

    /**
     * Create DTO from Request
     */
    public static function fromRequest(StoreMachineRequest|UpdateMachineRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            code: $request->validated('code'),
            description: $request->validated('description'),
            status: $request->validated('status'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'status' => $this->status,
        ]);
    }
}
