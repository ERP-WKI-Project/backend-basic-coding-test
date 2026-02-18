<?php

namespace App\Http\Requests\Machine;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLogEntryRequest extends FormRequest
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
            'event' => [
                'required',
                'string',
                Rule::in(['login_success', 'login_failed'])
            ],
            'log_message' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'event.required' => 'Tipe event harus diisi',
            'event.string' => 'Tipe event harus berupa teks',
            'event.in' => 'Tipe event tidak valid',
            'log_message.required' => 'Pesan log harus diisi',
            'log_message.string' => 'Pesan log harus berupa teks',
            'log_message.max' => 'Pesan log tidak boleh lebih dari 1000 karakter',
        ];
    }
}

