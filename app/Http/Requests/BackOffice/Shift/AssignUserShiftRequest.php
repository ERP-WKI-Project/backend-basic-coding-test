<?php

namespace App\Http\Requests\BackOffice\Shift;

use Illuminate\Foundation\Http\FormRequest;

class AssignUserShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'shift_date' => ['required', 'date', 'date_format:Y-m-d'],
            'machine_code' => ['sometimes', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID wajib diisi.',
            'user_id.integer' => 'User ID harus berupa angka.',
            'user_id.exists' => 'User tidak ditemukan.',
            'shift_id.required' => 'Shift ID wajib diisi.',
            'shift_id.integer' => 'Shift ID harus berupa angka.',
            'shift_id.exists' => 'Shift tidak ditemukan.',
            'shift_date.required' => 'Tanggal shift wajib diisi.',
            'shift_date.date' => 'Format tanggal shift tidak valid.',
            'shift_date.date_format' => 'Format tanggal shift harus YYYY-MM-DD.',
            'machine_code.string' => 'Kode mesin harus berupa teks.',
            'machine_code.max' => 'Kode mesin maksimal 100 karakter.',
        ];
    }
}