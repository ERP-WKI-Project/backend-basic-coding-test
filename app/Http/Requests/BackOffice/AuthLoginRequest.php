<?php

namespace App\Http\Requests\BackOffice;

use App\Support\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuthLoginRequest extends FormRequest
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
            'employee_number' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string']
        ];
    }

    /**
     * Custom validation error response
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = [];

        foreach ($validator->failed() as $field => $rules) {
            // Get first failed rule
            $ruleName = strtolower(array_key_first($rules));
            $errors[$field] = $this->mapRuleToErrorCode($ruleName);
        }

        throw new HttpResponseException(
            ApiResponse::error(
                $validator->errors()->first()
                    . (count($errors) > 1
                        ? ' (and ' . (count($errors) - 1) . ' more error)'
                        : ''),
                $errors,
                400
            )
        );
    }

    /**
     * Mapping errors
     */
    private function mapRuleToErrorCode(string $rule): string
    {
        return match ($rule) {
            'required' => 'error.required',
            'string'   => 'error.invalid_type',
            'size'     => 'error.invalid_length',
            'min'      => 'error.min',
            'max'      => 'error.max',
            'numeric'  => 'error.numeric',
            'digits'   => 'error.digits',
            'email'    => 'error.email',
            default    => 'error.invalid',
        };
    }
}
