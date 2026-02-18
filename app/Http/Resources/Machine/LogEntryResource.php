<?php

namespace App\Http\Resources\Machine;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'machine' => $this->machine ? [
                'code' => $this->machine->code,
                'name' => $this->machine->name,
            ] : null,
            'user' => $this->user ? [
                'employee_number' => $this->user->employee_number,
                'name' => $this->user->name,
            ] : null,
            'event' => $this->event,
            'log_message' => $this->log_message,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
