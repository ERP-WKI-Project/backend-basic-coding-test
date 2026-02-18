<?php

namespace App\DTOs;

final readonly class CreateUserDto
{

    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {
    }

    public static function fromRequest(\App\Http\Requests\BackOffice\CreateUserRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
            password: $request->input('password'),
        );
    }
}
