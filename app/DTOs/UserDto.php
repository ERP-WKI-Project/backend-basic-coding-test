<?php

namespace App\DTOs;

use App\Http\Requests\BackOffice\User\CreateUserRequest;
use App\Http\Requests\BackOffice\User\UpdateUserRequest;

readonly class UserDto
{
    public function __construct(
        public ?string $employee_number,
        public ?string $name,
        public ?string $email,
        public ?string $password = null,
    ) {}

    public static function fromRequest(CreateUserRequest|UpdateUserRequest $request): self
    {
        return new self(
            employee_number: $request->validated('employee_number'),
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->filled('password')
                ? $request->validated('password')
                : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'employee_number' => $this->employee_number,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ], fn ($value) => ! is_null($value));
    }
}
