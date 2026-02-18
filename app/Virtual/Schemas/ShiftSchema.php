<?php

namespace App\Virtual\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Shift',
    title: 'Shift',
    description: 'Shift master data model',
    type: 'object'
)]
class ShiftSchema
{
    #[OA\Property(type: 'string', example: '01HPJQXQ9XGFBQDGFCVJQFJ2KX')]
    public string $ulid;

    #[OA\Property(type: 'integer', example: 1)]
    public int $day_of_week;

    #[OA\Property(type: 'string', example: 'Senin')]
    public string $day_name;

    #[OA\Property(type: 'string', example: 'Shift Pagi')]
    public string $name;

    #[OA\Property(type: 'string', example: '07:00:00')]
    public string $start_time;

    #[OA\Property(type: 'string', example: '15:00:00')]
    public string $end_time;

    #[OA\Property(type: 'string', format: 'date-time')]
    public string $created_at;

    #[OA\Property(type: 'string', format: 'date-time')]
    public string $updated_at;
}

#[OA\Schema(
    schema: 'UserShift',
    title: 'UserShift',
    description: 'User shift assignment model',
    type: 'object'
)]
class UserShiftSchema
{
    #[OA\Property(type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(ref: '#/components/schemas/User')]
    public object $user;

    #[OA\Property(ref: '#/components/schemas/Shift')]
    public object $shift;

    #[OA\Property(ref: '#/components/schemas/Machine')]
    public object $machine;

    #[OA\Property(type: 'string', format: 'date', example: '2026-02-18')]
    public string $shift_date;

    #[OA\Property(type: 'string', format: 'date-time')]
    public string $created_at;

    #[OA\Property(type: 'string', format: 'date-time')]
    public string $updated_at;
}
