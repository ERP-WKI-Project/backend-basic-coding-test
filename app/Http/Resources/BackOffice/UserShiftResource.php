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
            'user_id' => $this->user_id,
            'shift_id' => $this->shift_id,
            'shift_date' => $this->shift_date->format('Y-m-d'),
            'machine_code' => $this->machine_code,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'employee_number' => $this->user->employee_number,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'shift' => $this->whenLoaded('shift', function () {
                return [
                    'id' => $this->shift->id,
                    'name' => $this->shift->name,
                    'day_of_week' => $this->shift->day_of_week,
                    'start_time' => $this->shift->start_time,
                    'end_time' => $this->shift->end_time,
                ];
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
