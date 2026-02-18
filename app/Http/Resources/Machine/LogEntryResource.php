<?php

namespace App\Http\Resources\Machine;

use App\Http\Resources\BackOffice\MachineResource;
use App\Http\Resources\BackOffice\UserResource;
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
            'id' => $this->id,
            'machine' => new MachineResource($this->whenLoaded('machine')),
            'user' => new UserResource($this->whenLoaded('user')),
            'event' => $this->event,
            'log_message' => $this->log_message,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
