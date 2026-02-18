<?php

namespace App\Http\Resources\Machine;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'employee_number' => $this->user->employee_number,
            'machineCode' => $this->machine_code,
            'event' => $this->event,
            'log_message' => $this->log_message
        ];
    }
}
