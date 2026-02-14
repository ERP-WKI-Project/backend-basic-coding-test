<?php

namespace App\Http\Requests\BackOffice\UserShift;

use Illuminate\Foundation\Http\FormRequest;

class CreateUpdateUserShiftRequest extends FormRequest
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
            'user_id' => ['required', 'exists:users,id'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'machine_code' => ['required', 'exists:machines,machine_code'],
            'shift_date' => ['required', 'date'],
        ];
    }
}
