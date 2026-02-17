<?php

namespace App\DTOs;

readonly class UserDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public int $id,
        public string $employee_number,
        public string $name,
        public ?string $email,
        public bool $email_verified,
        public ?\DateTime $created_at,
        public ?\DateTime $updated_at,
    ) {
        //
    }

    /**
     * Create UserDto from User model
     */
    public static function fromModel(\App\Models\User $user): self
    {
        return new self(
            id: $user->id,
            employee_number: $user->employee_number,
            name: $user->name,
            email: $user->email,
            email_verified: $user->email_verified_at !== null,
            created_at: $user->created_at,
            updated_at: $user->updated_at,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->email_verified,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
