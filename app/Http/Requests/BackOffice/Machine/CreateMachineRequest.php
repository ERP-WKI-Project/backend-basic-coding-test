<?php

namespace App\Http\Requests\BackOffice\Machine;

use Illuminate\Foundation\Http\FormRequest;

class CreateMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:machines,code'],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['sometimes', 'string', 'max:255', 'nullable'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode mesin wajib diisi.',
            'code.unique' => 'Kode mesin sudah digunakan.',
            'name.required' => 'Nama mesin wajib diisi.',
        ];
    }
}
