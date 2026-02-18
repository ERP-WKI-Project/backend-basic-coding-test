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
            'ulid' => $this->ulid,
            'shift_date' => $this->shift_date,
            'machine_code' => $this->machine_code,
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'shift' => $this->whenLoaded('shift', fn() => new ShiftResource($this->shift)),
            'machine' => $this->whenLoaded('machine', fn() => $this->machine ? new MachineResource($this->machine) : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

