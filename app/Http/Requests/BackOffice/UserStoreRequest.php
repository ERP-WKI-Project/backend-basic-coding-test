<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Support\ApiResponse;
use Illuminate\Validation\Rules\Password;

class UserStoreRequest extends FormRequest
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
            'employee_number' => ['required', 'string', 'size:6', 'unique:users,employee_number'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(8)->numbers()->mixedCase()->symbols()],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = [];

        foreach ($validator->failed() as $field => $rules) {

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
}
