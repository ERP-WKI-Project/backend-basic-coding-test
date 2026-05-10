<?php

namespace App\Http\Requests\BackOffice\Report;

use Illuminate\Foundation\Http\FormRequest;

class UserMachineActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'machine_code' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'Start date wajib diisi.',
            'start_date.date' => 'Format start date tidak valid.',
            'start_date.date_format' => 'Format start date harus Y-m-d.',
            'end_date.required' => 'End date wajib diisi.',
            'end_date.date' => 'Format end date tidak valid.',
            'end_date.date_format' => 'Format end date harus Y-m-d.',
            'end_date.after_or_equal' => 'End date harus sama atau setelah start date.',
            'user_id.exists' => 'User tidak ditemukan.',
        ];
    }
}
