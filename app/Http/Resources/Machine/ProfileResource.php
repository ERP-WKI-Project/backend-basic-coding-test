<?php

namespace App\Http\Resources\Machine;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'employee_number' => $this->employee_number,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}

