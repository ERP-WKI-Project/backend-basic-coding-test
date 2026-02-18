<?php

namespace App\Http\Requests\BackOffice;

use App\Enums\MachineStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/^[A-Z0-9-]+$/', 'max:50', 'unique:machines,code'],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', new Enum(MachineStatusEnum::class)],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode mesin wajib diisi.',
            'code.regex' => 'Kode mesin hanya boleh huruf besar, angka, dan tanda hubung.',
            'code.unique' => 'Kode mesin sudah terdaftar.',
            'name.required' => 'Nama mesin wajib diisi.',
            'status.required' => 'Status mesin wajib dipilih.',
            'status.enum' => 'Status mesin tidak valid.',
        ];
    }
}
