<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;

class TransferMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_machine_code' => ['required', 'string', 'exists:machines,machine_code'],
            'reason' => ['required', 'string', 'max:500'],
            'transfer_time' => ['nullable', 'date_format:Y-m-d H:i:s'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_machine_code.exists' => 'Selected machine does not exist.',
            'reason.required' => 'Transfer reason is required.',
        ];
    }
}
