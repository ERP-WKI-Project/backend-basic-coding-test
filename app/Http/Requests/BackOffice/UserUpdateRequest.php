<?php

namespace App\Http\Requests\BackOffice;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Password;
use App\Support\ApiResponse;

class UserUpdateRequest extends FormRequest
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
        $userId = $this->route('user') ?? $this->route('id');

        return [
            'employee_number' => ['sometimes', 'string', 'size:6', 'unique:users,employee_number,' . $userId,],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:users,email,' . $userId,],
            'password' => ['sometimes', Password::min(8)->numbers()->mixedCase()->symbols(),],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = [];

        foreach ($validator->errors()->messages() as $field => $messages) {

            if ($field === 'password') {
                $errors[$field] = 'error.password_strength';
                continue;
            }

            $rules = $validator->failed()[$field] ?? [];
            $rule = strtolower(array_key_first($rules));
            $errors[$field] = $this->mapRuleToErrorCode($rule);
        }

        throw new HttpResponseException(
            ApiResponse::error(
                $validator->errors()->first(),
                $errors,
                400
            )
        );
    }

    private function mapRuleToErrorCode(string $rule): string
    {
        return match ($rule) {
            'required' => 'error.required',
            'unique' => 'error.unique',
            'email' => 'error.email',
            'min' => 'error.min',
            'size' => 'error.size',
            default => 'error.invalid',
        };
    }

    protected function prepareForValidation(): void
    {
        $id = $this->route('user') ?? $this->route('id');

        $user = User::whereNull('deleted_at')->find($id);

        if (!$user) {
            throw new HttpResponseException(
                ApiResponse::error(
                    'User not found',
                    [
                        'user' => 'error.not_found',
                    ],
                    404
                )
            );
        }
    }
}
