<?php

namespace App\Http\Resources\BackOffice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->user ? new UserResource($this->user) : null,
            'shift' => $this->shift ? new ShiftResource($this->shift) : null,
            'machine' => $this->machine ? new MachineResource($this->machine) : null,
            'shift_date' => $this->shift_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
