<?php

namespace App\DTOs;

final readonly class UpdateMachineDto
{

    public function __construct(
        public string $name,
        public string $description,
    ) {
    }

    public static function fromRequest(\App\Http\Requests\BackOffice\UpdateMachineRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            description: $request->input('description'),
        );
    }
}
