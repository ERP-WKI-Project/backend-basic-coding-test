<?php

namespace App\Http\Requests\BackOffice;

use App\Enums\MachineLog\SeverityEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportMachineFailureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'failure_description' => ['required', 'string', 'max:1000'],
            'severity' => ['required', 'string', Rule::in(SeverityEnum::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'failure_description.required' => 'Failure description is required.',
            'severity.in' => 'Severity must be one of: low, medium, high.',
        ];
    }
}
