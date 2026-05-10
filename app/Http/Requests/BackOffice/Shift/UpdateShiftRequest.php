<?php

namespace App\Http\Requests\BackOffice\Shift;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'day_of_week' => ['sometimes', 'integer', 'min:1', 'max:7'],
            'start_time' => ['sometimes', 'date_format:H:i:s'],
            'end_time' => ['sometimes', 'date_format:H:i:s'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Nama shift maksimal 100 karakter.',
            'day_of_week.integer' => 'Hari kerja harus berupa angka.',
            'day_of_week.min' => 'Hari kerja minimal 1 (Senin).',
            'day_of_week.max' => 'Hari kerja maksimal 7 (Minggu).',
            'start_time.date_format' => 'Format waktu mulai harus HH:MM:SS.',
            'end_time.date_format' => 'Format waktu selesai harus HH:MM:SS.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');

            if ($startTime && $endTime && $startTime >= $endTime) {
                $validator->errors()->add('end_time', 'Waktu selesai harus setelah waktu mulai.');
            }
        });
    }
}