<?php

namespace App\Http\Requests\BackOffice\Shift;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,ulid'],
            'shift_id' => ['required', 'exists:shifts,ulid'],
            'shift_date' => ['required', 'date', 'date_format:Y-m-d'],
            'machine_id' => ['required', 'exists:machines,ulid'],
        ];
    }
}
