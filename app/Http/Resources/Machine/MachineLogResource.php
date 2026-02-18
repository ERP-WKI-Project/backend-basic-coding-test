<?php

namespace App\Http\Resources\Machine;

use App\Http\Resources\BackOffice\MachineResource;
use App\Http\Resources\BackOffice\UserResource;
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
            'machine' => new MachineResource($this->machine),
            'user' => new UserResource($this->user),
            'event' => $this->event,
            'log_message' => $this->log_message,
            'created_at' => $this->created_at,
        ];
    }
}
