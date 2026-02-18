<?php

namespace App\DTOs;

readonly class UserDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $name,
        public ?string $employee_number = null,
        public ?string $email = null,
        public ?string $password = null,
    )
    {
        //
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'] ?? null,
            password: $data['password'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->employee_number !== null) {
            $data['employee_number'] = $this->employee_number;
        }

        if ($this->password !== null) {
            $data['password'] = $this->password;
        }

        return $data;
    }
}

