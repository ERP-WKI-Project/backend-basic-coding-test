<?php

namespace App\DTOs;

readonly class MachineDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $type,
        public string $brand,
        public string $model,
        public string $serial_number,
        public string $capacity_per_hour,
        public string $capacity_unit,
        public string $production_line_id,
        public string $room_id,
        public string $purchase_date,
        public string $installation_date,
        public string $status,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            type: $data['type'],
            brand: $data['brand'],
            model: $data['model'],
            serial_number: $data['serial_number'],
            capacity_per_hour: $data['capacity_per_hour'],
            capacity_unit: $data['capacity_unit'],
            production_line_id: $data['production_line_id'],
            room_id: $data['room_id'],
            purchase_date: $data['purchase_date'],
            installation_date: $data['installation_date'],
            status: $data['status']
        );
    }

    public function toArray(): array
    {
        return array_filter([
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
            'status' => $this->status
        ], fn ($v) => ! is_null($v));
    }
}
