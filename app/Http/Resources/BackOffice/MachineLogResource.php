<?php

namespace App\Http\Resources\BackOffice;

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
            'event' => $this->event,
            'log_message' => $this->log_message,
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'machine' => $this->whenLoaded('machine', fn() => new MachineResource($this->machine)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

