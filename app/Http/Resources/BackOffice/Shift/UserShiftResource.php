<?php

namespace App\Http\Resources\BackOffice\Shift;

use App\Http\Resources\BackOffice\Machine\MachineResource;
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
            'id' => $this->ulid,
            'shift_date' => $this->shift_date instanceof \Carbon\Carbon 
                ? $this->shift_date->format('Y-m-d') 
                : $this->shift_date,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'employee_number' => $this->user?->employee_number,
            ],
            'shift_details' => new ShiftResource($this->whenLoaded('shift')),
            'machine' => new MachineResource($this->whenLoaded('machine')),
            'created_by' => [
                'id' => $this->createdBy?->id,
                'name' => $this->createdBy?->name,
                'employee_number' => $this->createdBy?->employee_number,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
