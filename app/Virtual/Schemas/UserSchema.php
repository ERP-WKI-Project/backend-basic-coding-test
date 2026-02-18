<?php

namespace App\Virtual\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    title: 'User',
    description: 'User model',
    type: 'object'
)]
class UserSchema
{
    #[OA\Property(type: 'string', example: '01HPJQXQ9XGFBQDGFCVJQFJ2KX')]
    public string $ulid;

    #[OA\Property(type: 'string', example: '123456')]
    public string $employee_number;

    #[OA\Property(type: 'string', example: 'John Doe')]
    public string $name;

    #[OA\Property(type: 'string', example: 'john@example.com')]
    public string $email;

    #[OA\Property(type: 'boolean', example: true)]
    public bool $is_active;

    #[OA\Property(type: 'string', format: 'date-time')]
    public string $created_at;

    #[OA\Property(type: 'string', format: 'date-time')]
    public string $updated_at;
}
