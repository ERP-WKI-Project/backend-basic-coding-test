<?php

namespace App\Http\Requests\BackOffice\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
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
            'employee_number' => ['required', 'numeric', 'digits:6', Rule::unique('users', 'employee_number')],
            'name' => ['required', 'string'],
            'email' => ['nullable', 'email'],
            'password' => ['required', 'string']
        ];
    }
}
