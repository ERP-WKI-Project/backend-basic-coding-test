<?php

namespace App\Http\Resources\Machine;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'machine_code' => $this->machine_code,
            'event' => $this->event,
            'log_message' => $this->log_message,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
