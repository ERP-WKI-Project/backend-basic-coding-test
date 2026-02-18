<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserShiftRequest extends FormRequest
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
            'employee_number' => ['required', 'string', 'size:6', 'exists:users,employee_number'],
            'shift_ulid' => ['required', 'string', 'size:26', 'exists:shifts,ulid'],
            'shift_date' => ['required', 'date', 'date_format:Y-m-d'],
            'machine_code' => ['nullable', 'string', 'exists:machines,machine_code'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_number.required' => 'Employee number is required.',
            'employee_number.size' => 'Employee number must be exactly 6 characters.',
            'employee_number.exists' => 'The selected employee does not exist.',
            'shift_ulid.required' => 'Shift ULID is required.',
            'shift_ulid.size' => 'Shift ULID must be exactly 26 characters.',
            'shift_ulid.exists' => 'The selected shift does not exist.',
            'shift_date.required' => 'Shift date is required.',
            'shift_date.date' => 'Shift date must be a valid date.',
            'shift_date.date_format' => 'Shift date must be in Y-m-d format (e.g., 2026-02-18).',
            'machine_code.exists' => 'The selected machine does not exist.',
        ];
    }
}

