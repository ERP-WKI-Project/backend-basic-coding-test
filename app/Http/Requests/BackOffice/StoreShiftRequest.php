<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'day_of_week.required' => 'Hari wajib dipilih.',
            'day_of_week.between' => 'Hari harus antara 1 (Senin) sampai 7 (Minggu).',
            'name.required' => 'Nama shift wajib diisi.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'start_time.date_format' => 'Format jam mulai harus HH:MM:SS.',
            'end_time.required' => 'Jam selesai wajib diisi.',
            'end_time.date_format' => 'Format jam selesai harus HH:MM:SS.',
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
        ];
    }
}
