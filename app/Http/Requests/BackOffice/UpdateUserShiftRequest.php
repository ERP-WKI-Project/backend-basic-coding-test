<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'shift_id' => ['sometimes', 'required', 'integer', 'exists:shifts,id'],
            'machine_code' => ['sometimes', 'required', 'string', 'exists:machines,machine_code'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'Selected user does not exist.',
            'shift_id.exists' => 'Selected shift does not exist.',
            'machine_code.exists' => 'Selected machine does not exist.',
        ];
    }
}
