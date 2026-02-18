<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Support\ApiResponse;

class ReportUserMachineActivityRequest extends FormRequest
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'employee_number' => ['sometimes', 'string', 'max:20'],
            'machine_code' => ['sometimes', 'string', 'max:255'],
            'event' => ['sometimes', 'string', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
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
            'required' => 'error.required',
            'date' => 'error.invalid_date',
            'after_or_equal' => 'error.date_range',
            'integer' => 'error.integer',
            'min' => 'error.min',
            'max' => 'error.max',
            default => 'error.invalid',
        };
    }
}
