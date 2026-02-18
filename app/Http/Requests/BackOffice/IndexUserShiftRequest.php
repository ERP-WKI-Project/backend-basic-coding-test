<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;

class IndexUserShiftRequest extends FormRequest
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
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'filter.employee_number' => ['nullable', 'string', 'size:6', 'exists:users,employee_number'],
            'filter.shift_date' => ['nullable', 'date'],
            'filter.machine_code' => ['nullable', 'string', 'exists:machines,machine_code'],
        ];
    }
}

