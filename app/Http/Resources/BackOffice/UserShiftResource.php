<?php

namespace App\Http\Resources\BackOffice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserShiftResource extends JsonResource
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
            'shift_date' => $this->shift_date->format('Y-m-d'),
            'user' => new UserResource($this->whenLoaded('user')),
            'shift' => new ShiftResource($this->whenLoaded('shift')),
            'machine' => new MachineResource($this->whenLoaded('machine')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
