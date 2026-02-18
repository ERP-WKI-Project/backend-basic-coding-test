<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;

class UserMachineActivityRequest extends FormRequest
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
            'start_date' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:end_date'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'machine_code' => ['nullable', 'string', 'exists:machines,machine_code'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Start date is required',
            'start_date.date' => 'Start date must be a valid date',
            'start_date.date_format' => 'Start date must be in Y-m-d format',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date',
            'end_date.required' => 'End date is required',
            'end_date.date' => 'End date must be a valid date',
            'end_date.date_format' => 'End date must be in Y-m-d format',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
            'user_id.exists' => 'Selected user does not exist',
            'machine_code.exists' => 'Selected machine does not exist',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'start_date' => 'start date',
            'end_date' => 'end date',
            'user_id' => 'user',
            'machine_code' => 'machine code',
        ];
    }
}
