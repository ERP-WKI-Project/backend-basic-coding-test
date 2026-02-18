<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Support\ApiResponse;

class ShiftIndexRequest extends FormRequest
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'employee_number' => ['sometimes', 'string', 'max:6'],
            'machine_code' => ['sometimes', 'string', 'max:255'],
            'search' => ['sometimes', 'string', 'max:100'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = [];

        foreach ($validator->failed() as $field => $rules) {
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
            'integer' => 'error.integer',
            'min' => 'error.min',
            'max' => 'error.max',
            'exists' => 'error.not_found',
            'date' => 'error.invalid_date',
            'string' => 'error.invalid_type',
            default => 'error.invalid',
        };
    }
}
