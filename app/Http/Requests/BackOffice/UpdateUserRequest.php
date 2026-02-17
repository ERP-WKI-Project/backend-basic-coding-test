<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;

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
            'employee_number' => 'sometimes|string|size:6|unique:users,employee_number,' . $this->user->id,
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $this->user->id,
            'password' => 'nullable|min:6',
        ];
    }
}
