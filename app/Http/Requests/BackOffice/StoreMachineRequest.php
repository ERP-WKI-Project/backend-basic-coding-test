<?php

namespace App\Http\Requests\BackOffice;

use App\Enums\MachineStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMachineRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'max:255',
                'unique:machines,code',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::enum(MachineStatus::class),
            ],
        ];
    }
}
