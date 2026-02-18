<?php

namespace App\Http\Resources\Machine;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MachineLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'machine_code' => $this->machine_code,
            'user' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? null,
            ],
            'event' => $this->event?->value,
            'event_label' => $this->event?->label(),
            'severity' => $this->severity?->value,
            'severity_label' => $this->severity?->label(),
            'log_message' => $this->log_message,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
