<?php

namespace App\Http\Requests\BackOffice\Machine;

use Illuminate\Foundation\Http\FormRequest;

class MachineIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => 'Parameter page harus berupa angka.',
            'page.min' => 'Parameter page minimal 1.',
            'limit.integer' => 'Parameter limit harus berupa angka.',
            'limit.min' => 'Parameter limit minimal 1.',
            'limit.max' => 'Parameter limit maksimal 100.',
            'search.string' => 'Parameter search harus berupa teks.',
            'search.max' => 'Parameter search maksimal 100 karakter.',
        ];
    }
}
