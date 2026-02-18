<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_number' => 'required|string|size:6|unique:users',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users',
            'password' => 'string|min:6',
        ];
    }
}
