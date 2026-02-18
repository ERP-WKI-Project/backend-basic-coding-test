<?php

namespace App\Http\Requests\BackOffice;

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
            'user_id' => ['required', 'exists:users,id'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'machine_id' => ['required', 'exists:machines,id'],
            'shift_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Karyawan wajib dipilih.',
            'user_id.exists' => 'Karyawan tidak ditemukan.',
            'shift_id.required' => 'Shift wajib dipilih.',
            'shift_id.exists' => 'Shift tidak ditemukan.',
            'machine_id.required' => 'Mesin wajib dipilih.',
            'machine_id.exists' => 'Mesin tidak ditemukan.',
            'shift_date.required' => 'Tanggal shift wajib diisi.',
            'shift_date.date' => 'Format tanggal tidak valid.',
            'shift_date.after_or_equal' => 'Tanggal shift tidak boleh di masa lalu.',
        ];
    }
}
