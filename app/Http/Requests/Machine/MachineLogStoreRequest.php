<?php

namespace App\Http\Requests\Machine;

use App\Enums\MachineLog\EventEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Support\ApiResponse;
use Illuminate\Validation\Rule;

class MachineLogStoreRequest extends FormRequest
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
            'event' => ['required', 'string', 'max:100', Rule::in(array_column(EventEnum::cases(), 'value'))],
            'message' => ['required', 'string', 'max:1000'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = [];

        foreach ($validator->failed() as $field => $rules) {
            $rule = strtolower(array_key_first($rules));
            $errors[$field] = match ($rule) {
                'required' => 'error.required',
                'string' => 'error.invalid_type',
                'in' => 'error.invalid_enum',
                'max' => 'error.max',
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
