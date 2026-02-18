<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;

class IndexMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'filter' => ['nullable', 'array'],
            'filter.machine_code' => ['nullable', 'string', 'max:255'],
            'filter.name' => ['nullable', 'string', 'max:255'],
            'filter.description' => ['nullable', 'string', 'max:255'],
            'filter.status' => ['nullable', 'string', 'in:active,inactive,maintenance'],
            'filter.trashed' => ['nullable', 'string', 'in:with,only'],
            'sort' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => 'search query',
            'limit' => 'limit',
            'filter.machine_code' => 'machine code filter',
            'filter.name' => 'name filter',
            'filter.description' => 'description filter',
            'filter.status' => 'status filter',
            'filter.trashed' => 'trashed filter',
        ];
    }

    public function messages(): array
    {
        return [
            'limit.max' => 'Maximum limit is 100',
            'limit.min' => 'Minimum limit is 1',
            'filter.trashed.in' => 'Trashed filter must be either "with" or "only"',
            'filter.status.in' => 'Status must be one of: active, inactive, maintenance',
        ];
    }
}
