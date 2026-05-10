<?php

namespace App\Http\Requests\Machine;

use App\Enums\MachineLog\LogEntryTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'machine_code' => ['required', 'string'],
            'type' => ['required', 'string', Rule::enum(LogEntryTypeEnum::class)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'machine_code.required' => 'Kode mesin wajib diisi.',
            'type.required' => 'Tipe log entry wajib diisi.',
            'type.enum' => 'Tipe log entry tidak valid.',
            'description.max' => 'Deskripsi maksimal 500 karakter.',
        ];
    }
}
