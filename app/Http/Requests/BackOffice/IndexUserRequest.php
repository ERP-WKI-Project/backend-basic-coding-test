<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;

class IndexUserRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'filter' => ['nullable', 'array'],
            'filter.employee_number' => ['nullable', 'string', 'max:6'],
            'filter.name' => ['nullable', 'string', 'max:255'],
            'filter.email' => ['nullable', 'string', 'max:255'],
            'filter.email_verified' => ['nullable', 'boolean'],
            'filter.trashed' => ['nullable', 'string', 'in:with,only'],
            'sort' => ['nullable', 'string'],
        ];
    }


    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'q' => 'search query',
            'limit' => 'limit',
            'filter.employee_number' => 'employee number filter',
            'filter.name' => 'name filter',
            'filter.email' => 'email filter',
            'filter.email_verified' => 'email verified filter',
            'filter.trashed' => 'trashed filter',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'limit.max' => 'Maximum limit is 100',
            'limit.min' => 'Minimum limit is 1',
            'filter.trashed.in' => 'Trashed filter must be either "with" or "only"',
        ];
    }
}

