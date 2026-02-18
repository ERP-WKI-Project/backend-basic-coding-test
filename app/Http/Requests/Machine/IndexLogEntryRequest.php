<?php

namespace App\Http\Requests\Machine;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLogEntryRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'filter.event' => [
                'nullable',
                'string',
                Rule::in(['login_success', 'login_failed', 'start_work', 'end_work', 'machine_error', 'maintenance'])
            ],
            'filter.date_from' => ['nullable', 'date'],
            'filter.date_to' => ['nullable', 'date', 'after_or_equal:filter.date_from'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'limit.integer' => 'Limit harus berupa angka',
            'limit.min' => 'Limit minimal 1',
            'limit.max' => 'Limit maksimal 100',
            'filter.event.in' => 'Tipe event tidak valid',
            'filter.date_from.date' => 'Format tanggal awal tidak valid',
            'filter.date_to.date' => 'Format tanggal akhir tidak valid',
            'filter.date_to.after_or_equal' => 'Tanggal akhir harus lebih besar atau sama dengan tanggal awal',
        ];
    }
}

