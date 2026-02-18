<?php

namespace App\Http\Resources\Machine\LogEntry;

use App\Http\Resources\BackOffice\Machine\MachineResource;
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
            'id'           => $this->ulid,
            'event'        => $this->event,
            'message'      => $this->log_message,
            'machine'      => new MachineResource($this->whenLoaded('machine')),
            'user'         => [
                'name'            => $this->user?->name,
                'employee_number' => $this->user?->employee_number,
            ],
            "user_shift" => [
                "name"            => $this->userShift?->shift?->name,
                "start_time"      => $this->userShift?->shift?->start_time,
                "end_time"        => $this->userShift?->shift?->end_time,
                "shift_date"      => $this->userShift?->shift_date
            ],
            'logged_at'    => $this->created_at?->format('Y-m-d H:i:s'),
            'time_ago'     => $this->created_at?->diffForHumans(),
        ];
    }
}
