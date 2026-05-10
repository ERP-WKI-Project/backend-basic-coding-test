<?php

namespace App\Http\Requests\BackOffice\Shift;

use Illuminate\Foundation\Http\FormRequest;

class CreateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama shift wajib diisi.',
            'name.max' => 'Nama shift maksimal 100 karakter.',
            'day_of_week.required' => 'Hari kerja wajib diisi.',
            'day_of_week.integer' => 'Hari kerja harus berupa angka.',
            'day_of_week.min' => 'Hari kerja minimal 1 (Senin).',
            'day_of_week.max' => 'Hari kerja maksimal 7 (Minggu).',
            'start_time.required' => 'Waktu mulai wajib diisi.',
            'start_time.date_format' => 'Format waktu mulai harus HH:MM:SS.',
            'end_time.required' => 'Waktu selesai wajib diisi.',
            'end_time.date_format' => 'Format waktu selesai harus HH:MM:SS.',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
        ];
    }
}