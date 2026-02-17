<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;

class StoreMachineRequest extends FormRequest
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
            'code' => 'required|string|size:7|unique:machines',
            'name' => 'required|string|max:50',
            'type' => 'required|string|max:50',
            'brand' => 'required|string|max:50',
            'model' => 'required|string|max:50',
            'serial_number' => 'required|string|max:50|unique:machines',
            'capacity_per_hour' => 'required|numeric',
            'capacity_unit' => 'required|string|max:50',
            'production_line_id' => 'required|exists:production_lines,id',
            'room_id' => 'required|exists:rooms,id',
            'purchase_date' => 'nullable|date',
            'installation_date' => 'nullable|date',
            'status' => 'required|string|in:active,maintenance,breakdown,retired',
            'notes' => 'nullable|string',
        ];
    }
}
