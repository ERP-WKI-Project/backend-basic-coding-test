<?php

namespace App\DTOs\User;

use App\Http\Requests\BackOffice\UpdateUserRequest;

readonly class UpdateUserDto
{
    public function __construct(
        public ?string $employeeNumber = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
        public bool $hasEmail = false,
    ) {}

    public static function fromRequest(UpdateUserRequest $request): self
    {
        return new self(
            employeeNumber: $request->validated('employee_number'),
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            hasEmail: $request->has('email'),
        );
    }

    public function toArray(): array
    {
        $data = array_filter([
            'employee_number' => $this->employeeNumber,
            'name' => $this->name,
            'password' => $this->password,
        ], fn ($value) => $value !== null);

        if ($this->hasEmail) {
            $data['email'] = $this->email;
        }

        return $data;
    }
}
