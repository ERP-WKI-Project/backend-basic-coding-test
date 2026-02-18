<?php

namespace App\Http\Requests\BackOffice;

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
            'day_of_week' => ['sometimes', 'integer', 'between:1,7'],
            'name' => ['sometimes', 'string', 'max:255'],
            'start_time' => ['sometimes', 'date_format:H:i:s'],
            'end_time' => ['sometimes', 'date_format:H:i:s', 'after:start_time'],
        ];
    }
}
