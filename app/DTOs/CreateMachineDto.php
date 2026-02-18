<?php

namespace App\DTOs;

final readonly class CreateMachineDto
{

    public function __construct(
        public string $machineCode,
        public string $name,
    ) {
    }

    public static function fromRequest(\App\Http\Requests\BackOffice\CreateMachineRequest $request): self
    {
        return new self(
            machineCode: $request->input('machine_code'),
            name: $request->input('name'),
        );
    }
}
