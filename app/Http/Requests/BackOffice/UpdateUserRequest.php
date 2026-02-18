<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'employee_number' => ['sometimes', 'string', 'size:6', Rule::unique('users', 'employee_number')->ignore($this->route('user'))],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
        ];
    }
}
