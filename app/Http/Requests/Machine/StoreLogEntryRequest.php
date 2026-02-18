<?php

namespace App\Http\Requests\Machine;

use App\Enums\MachineLog\EventEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreLogEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event' => ['required', 'string', new Enum(EventEnum::class)],
            'log_message' => ['required', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'event.required' => 'Event wajib diisi.',
            'event.enum' => 'Event tidak valid.',
            'log_message.required' => 'Pesan log wajib diisi.',
            'log_message.max' => 'Pesan log maksimal 1000 karakter.',
        ];
    }
}
