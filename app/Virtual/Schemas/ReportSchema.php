<?php

namespace App\Virtual\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ActivityReport',
    title: 'Activity Report',
    description: 'User machine activity report',
    type: 'object'
)]
class ActivityReportSchema
{
    #[OA\Property(property: 'summary', type: 'object')]
    public object $summary;

    #[OA\Property(property: 'activities', type: 'array', items: new OA\Items(type: 'object'))]
    public array $activities;
}

#[OA\Schema(
    schema: 'LogEntry',
    title: 'Log Entry',
    description: 'Machine log entry',
    type: 'object'
)]
class LogEntrySchema
{
    #[OA\Property(type: 'string', example: '01ABCDEF1234567890ABCDEF')]
    public string $ulid;

    #[OA\Property(type: 'object')]
    public object $machine;

    #[OA\Property(type: 'object')]
    public object $user;

    #[OA\Property(type: 'string', example: 'PRODUCTION_START')]
    public string $event;

    #[OA\Property(type: 'string', example: 'Started batch #12345')]
    public string $log_message;

    #[OA\Property(type: 'object', nullable: true)]
    public ?object $metadata;

    #[OA\Property(type: 'string', format: 'date-time')]
    public string $created_at;
}
