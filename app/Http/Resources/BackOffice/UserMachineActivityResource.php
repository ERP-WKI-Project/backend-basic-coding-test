<?php

namespace App\Http\Resources\BackOffice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMachineActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'log_id' => $this->id,
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user->name ?? null,
                'employee_number' => $this->user->employee_number ?? null,
            ],
            'machine' => [
                'code' => $this->machine_code,
                'name' => $this->machine->name ?? null,
            ],
            'event' => $this->event,
            'message' => $this->log_message,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
