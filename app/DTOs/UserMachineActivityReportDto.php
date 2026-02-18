<?php

namespace App\DTOs;

use Illuminate\Support\Carbon;

readonly class UserMachineActivityReportDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public array $user,
        public array $machine,
        public ?array $shift,
        public ?string $shift_date,
        public string $event,
        public string $log_message,
        public ?string $log_time,
    ) {
        //
    }

    /**
     * Create DTO from query row
     */
    public static function fromRow(object $row): self
    {
        $logTime = null;
        if (!empty($row->log_created_at)) {
            $logTime = Carbon::parse($row->log_created_at)->toIso8601String();
        }

        $shift = null;
        if (!empty($row->shift_id)) {
            $shift = [
                'id' => (int) $row->shift_id,
                'name' => $row->shift_name,
                'day_of_week' => $row->shift_day_of_week !== null ? (int) $row->shift_day_of_week : null,
                'start_time' => $row->shift_start_time,
                'end_time' => $row->shift_end_time,
            ];
        }

        return new self(
            user: [
                'id' => (int) $row->user_id,
                'employee_number' => $row->user_employee_number,
                'name' => $row->user_name,
                'email' => $row->user_email,
            ],
            machine: [
                'machine_code' => $row->machine_code,
                'name' => $row->machine_name,
                'description' => $row->machine_description,
                'location' => $row->machine_location,
                'status' => $row->machine_status,
            ],
            shift: $shift,
            shift_date: $row->shift_date,
            event: $row->event,
            log_message: $row->log_message,
            log_time: $logTime,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'user' => $this->user,
            'machine' => $this->machine,
            'shift' => $this->shift,
            'shift_date' => $this->shift_date,
            'event' => $this->event,
            'log_message' => $this->log_message,
            'log_time' => $this->log_time,
        ];
    }
}
