<?php

namespace App\DTOs;

use App\Http\Requests\BackOffice\User\StoreUserRequest;
use App\Http\Requests\BackOffice\User\UpdateUserRequest;

readonly class UserDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $name,
        public ?string $email,
        public ?string $password = null,
        public string $employee_number,
    ) {}

    public static function fromRequest(StoreUserRequest|UpdateUserRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            employee_number: $request->validated('employee_number'),
            email: $request->validated('email'),
            password: $request->filled('password') 
                ? $request->validated('password')
                : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'employee_number' => $this->employee_number,
        ], fn($value) => $value !== null);
    }
}
