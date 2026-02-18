<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userUlid = $this->route('user');
        $user = \App\Models\User::where('ulid', $userUlid)->first();
        $userId = $user ? $user->id : null;

        return [
            'employee_number' => ['sometimes', 'string', 'regex:/^[0-9]{6}$/', 'unique:users,employee_number,'.$userId],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$userId],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_number.regex' => 'Nomor karyawan harus terdiri dari 6 digit angka.',
            'employee_number.unique' => 'Nomor karyawan sudah terdaftar.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
