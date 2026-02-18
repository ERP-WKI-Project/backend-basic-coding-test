<?php

namespace App\Http\Resources\BackOffice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportMachineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // user
            'employee_number' => $this->user?->employee_number,
            'employee_name' => $this->user?->name,

            // machine
            'machine_code' => $this->machine_code,
            'machine_name' => $this->machine?->name ?? null,
            'production_line' => $this->machine?->productionLine?->name ?? null,
            'room' => $this->machine?->room?->name ?? null,

            // activity
            'event' => $this->event,
            'log_message' => $this->log_message,

            // shift context
            'shift_date' => $this->shift_date,
            'shift_name' => $this->shift?->name ?? null,

            // time
            'logged_at' => $this->created_at,
            'logged_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
