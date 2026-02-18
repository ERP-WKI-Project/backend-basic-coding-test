<?php

namespace App\DTOs;

class UserDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $employeeNumber,
        public readonly ?string $password = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            employeeNumber: $data['employee_number'],
            password: $data['password'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'employee_number' => $this->employeeNumber,
        ];

        if ($this->password !== null) {
            $data['password'] = $this->password;
        }

        return $data;
    }
}
