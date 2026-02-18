<?php

namespace App\DTOs;

final readonly class UpdateUserDto
{

    public function __construct(
        public string $name,
        public string $email
    ) {
    }

    public static function fromRequest(\App\Http\Requests\BackOffice\UpdateUserRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
        );
    }
}
