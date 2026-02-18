<?php

namespace App\DTOs;

readonly class UserDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $name,
        public string $employee_number,
        public string $email,
        public ?string $password = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            employee_number: $data['employee_number'],
            email: $data['email'],
            password: $data['password'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'employee_number' => $this->employee_number,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ], fn ($v) => ! is_null($v));
    }
}
