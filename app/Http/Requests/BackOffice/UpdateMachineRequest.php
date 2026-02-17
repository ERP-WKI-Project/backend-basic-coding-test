<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMachineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|size:7|unique:machines,code,' . $this->machine->id,
            'name' => 'sometimes|string|max:50',
            'type' => 'sometimes|string|max:50',
            'brand' => 'sometimes|string|max:50',
            'model' => 'sometimes|string|max:50',
            'serial_number' => 'sometimes|string|max:50|unique:machines,serial_number,' . $this->machine->id,
            'capacity_per_hour' => 'sometimes|numeric',
            'capacity_unit' => 'sometimes|string|max:50',
            'production_line_id' => 'sometimes|exists:production_lines,id',
            'room_id' => 'sometimes|exists:rooms,id',
            'purchase_date' => 'nullable|date',
            'installation_date' => 'nullable|date',
            'status' => 'sometimes|string|in:active,maintenance,breakdown,retired',
            'notes' => 'nullable|string',
        ];
    }
}
