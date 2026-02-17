<?php

namespace App\Http\Resources\BackOffice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MachineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'brand' => $this->brand,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'capacity_per_hour' => $this->capacity_per_hour,
            'capacity_unit' => $this->capacity_unit,
            'production_line_id' => $this->production_line_id,
            'room_id' => $this->room_id,
            'purchase_date' => $this->purchase_date,
            'installation_date' => $this->installation_date,
            'status' => $this->status,
            'notes' => $this->notes
        ];
    }
}
