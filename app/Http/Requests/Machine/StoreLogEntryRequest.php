<?php

namespace App\Http\Requests;

use App\Enums\MachineLog\MachineLogEventEnum;
use App\Enums\MachineLog\SeverityEnum;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'machine_code' => ['required', 'string', 'exists:machines,machine_code'],
            'event' => ['required', Rule::enum(MachineLogEventEnum::class)],
            'log_message' => ['required', 'string', 'max:65535'],
            'severity' => ['nullable', Rule::enum(SeverityEnum::class)],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'machine_code.required' => 'Machine code is required',
            'machine_code.exists' => 'Machine code does not exist',
            'event.required' => 'Event type is required',
            'log_message.required' => 'Log message is required',
            'log_message.max' => 'Log message is too long',
        ];
    }
}
