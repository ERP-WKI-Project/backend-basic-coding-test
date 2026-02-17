<?php

namespace App\Http\Requests\Machine\LogEntry;

use App\Enums\MachineLog\EventEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreLogEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'machine_id'   => ['required', 'string', 'exists:machines,ulid'],
            'event'        => ['required', 'string', new Enum(EventEnum::class)],
            'log_message'  => ['required', 'string', 'max:5000'],
        ];
    }
}
