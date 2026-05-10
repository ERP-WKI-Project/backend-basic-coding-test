<?php

namespace App\Http\Requests\BackOffice\Shift;

use Illuminate\Foundation\Http\FormRequest;

class ShiftIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:100'],
            'day_of_week' => ['sometimes', 'integer', 'min:1', 'max:7'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => 'Parameter page harus berupa angka.',
            'page.min' => 'Parameter page minimal 1.',
            'limit.integer' => 'Parameter limit harus berupa angka.',
            'limit.min' => 'Parameter limit minimal 1.',
            'limit.max' => 'Parameter limit maksimal 100.',
            'search.string' => 'Parameter search harus berupa teks.',
            'search.max' => 'Parameter search maksimal 100 karakter.',
            'day_of_week.integer' => 'Hari kerja harus berupa angka.',
            'day_of_week.min' => 'Hari kerja minimal 1 (Senin).',
            'day_of_week.max' => 'Hari kerja maksimal 7 (Minggu).',
        ];
    }
}