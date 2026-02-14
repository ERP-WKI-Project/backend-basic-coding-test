<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMachineRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'machine_code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('machines', 'machine_code')->ignore($this->route('machine')),
                'regex:/^[A-Z0-9\-]+$/'
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'machine_code.required' => 'Kode mesin wajib diisi.',
            'machine_code.string' => 'Kode mesin harus berupa teks.',
            'machine_code.max' => 'Kode mesin maksimal 50 karakter.',
            'machine_code.unique' => 'Kode mesin sudah terdaftar.',
            'machine_code.regex' => 'Kode mesin hanya boleh mengandung huruf kapital, angka, dan tanda strip (-).',
            'name.required' => 'Nama mesin wajib diisi.',
            'name.string' => 'Nama mesin harus berupa teks.',
            'name.max' => 'Nama mesin maksimal 255 karakter.',
            'description.string' => 'Deskripsi harus berupa teks.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
        ];
    }
}
