<?php

namespace App\Http\Requests\BackOffice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Support\ApiResponse;

class ShiftUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('shift'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:user_shifts,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'shift_date' => ['required', 'date'],
            'machine_code' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = [];

        foreach ($validator->failed() as $field => $rules) {
            $rule = strtolower(array_key_first($rules));
            $errors[$field] = match ($rule) {
                'required' => 'error.required',
                'exists' => 'error.not_found',
                'date' => 'error.invalid_date',
                default => 'error.invalid',
            };
        }

        throw new HttpResponseException(
            ApiResponse::error(
                $validator->errors()->first(),
                $errors,
                400
            )
        );
    }
}
