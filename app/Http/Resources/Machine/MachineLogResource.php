<?php

namespace App\Http\Resources\Machine;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MachineLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'machine_code' => $this->machine_code,
            'user' => [
                'employee_number' => $this->user->employee_number,
                'name' => $this->user->name,
            ],
            'event' => $this->event,
            'log_message' => $this->log_message,
            'created_at' => $this->created_at,
        ];
    }
}
