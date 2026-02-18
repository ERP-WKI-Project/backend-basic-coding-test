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
            'user' => [
                'id' => $this->user->id ?? null,
                'ulid' => $this->user->ulid ?? null,
                'name' => $this->user->name ?? null,
                'email' => $this->user->email ?? null,
            ],
            'shift' => [
                'id' => $this->shift->id ?? null,
                'name' => $this->shift->name ?? null,
                'day_of_week' => $this->shift->day_of_week ?? null,
                'start_time' => $this->shift->start_time ?? null,
                'end_time' => $this->shift->end_time ?? null,
            ],
            'shift_date' => $this->shift_date?->toDateString(),
            'machine' => [
                'machine_code' => $this->machine->machine_code ?? $this->machine_code,
                'name' => $this->machine->name ?? null,
            ],
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
