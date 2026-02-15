<?php

namespace App\DTOs\User;

use App\Http\Requests\BackOffice\StoreUserRequest;

readonly class CreateUserDto
{
    public function __construct(
        public string $employeeNumber,
        public string $name,
        public ?string $email,
        public string $password,
    ) {}

    public static function fromRequest(StoreUserRequest $request): self
    {
        return new self(
            employeeNumber: $request->validated('employee_number'),
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        );
    }

    public function toArray(): array
    {
        return [
            'employee_number' => $this->employeeNumber,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
