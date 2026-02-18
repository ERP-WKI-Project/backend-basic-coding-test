<?php

namespace App\Http\Requests\Machine;

use App\Enums\MachineStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLogEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'machine_code' => [
                'required',
                'string',
                Rule::exists('machines', 'code')->where('status', MachineStatus::ACTIVE->value),
            ],
            'event' => [
                'required',
                'string',
                'max:255',
            ],
            'log_message' => [
                'required',
                'string',
            ],
        ];
    }
}
