<?php

namespace App\Virtual\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Machine',
    title: 'Machine',
    description: 'Machine model',
    type: 'object'
)]
class MachineSchema
{
    #[OA\Property(type: 'string', example: '01HPJQXQ9XGFBQDGFCVJQFJ2KX')]
    public string $ulid;

    #[OA\Property(type: 'string', example: 'FILLING-MACHINE-001')]
    public string $code;

    #[OA\Property(type: 'string', example: 'Filling Machine Line 1')]
    public string $name;

    #[OA\Property(type: 'string', example: 'Plant A - Floor 1', nullable: true)]
    public ?string $location;

    #[OA\Property(type: 'string', example: 'active')]
    public string $status;

    #[OA\Property(type: 'string', example: 'Aktif')]
    public string $status_label;

    #[OA\Property(type: 'string', example: 'Machine for filling products', nullable: true)]
    public ?string $description;

    #[OA\Property(type: 'string', format: 'date-time')]
    public string $created_at;

    #[OA\Property(type: 'string', format: 'date-time')]
    public string $updated_at;
}
