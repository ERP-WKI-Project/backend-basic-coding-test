<?php

namespace App\Http\Requests\BackOffice\Report;

use Illuminate\Foundation\Http\FormRequest;

class UserMachineActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search'     => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date', 'before_or_equal:today'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date', 'before_or_equal:today'],
            'user_id'    => ['nullable', 'string', 'exists:users,id'],
            'machine_id' => ['nullable', 'string', 'exists:machines,ulid'],
            'shift_id'   => ['nullable', 'integer', 'exists:shifts,id'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
