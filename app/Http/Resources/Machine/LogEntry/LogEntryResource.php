<?php

namespace App\Http\Resources\Machine\LogEntry;

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
            'id'           => $this->ulid, // Use ULID instead of internal ID
            'machine_code' => $this->machine_code,
            'event'        => $this->event,
            'message'      => $this->log_message,
            
            // Transform relationship into a nested object or string
            'operator'     => [
                'name'            => $this->user?->name,
                'employee_number' => $this->user?->employee_number,
            ],
            'logged_at'    => $this->created_at?->format('Y-m-d H:i:s'),
            'time_ago'     => $this->created_at?->diffForHumans(),
        ];
    }
}
