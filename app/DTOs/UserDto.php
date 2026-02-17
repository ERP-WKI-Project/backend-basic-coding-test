<?php

namespace App\DTOs;

readonly class UserDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $employee_number,
        public string $name,
        public ?string $email = null,
        public ?string $password = null,
    )
    {
        //
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            employee_number: $data['employee_number'],
            name: $data['name'],
            email: $data['email'] ?? null,
            password: $data['password'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'employee_number' => $this->employee_number,
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password !== null) {
            $data['password'] = $this->password;
        }

        return $data;
    }
}

