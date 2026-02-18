<?php

namespace App\Http\Requests\Machine;

use App\Enums\MachineLog\EventEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateMachineLogRequest extends FormRequest
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
            'user_id' => ['required', 'exists:users,id'],
            'machine_code' => ['required', 'exists:machines,machine_code'],
            'event' => ['required', new Enum(EventEnum::class)],
            'log_message' => ['required']
        ];
    }
}
