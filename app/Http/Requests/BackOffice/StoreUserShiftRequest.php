<?php

namespace App\Http\Requests\BackOffice;

use App\Enums\MachineStatus;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserShiftRequest extends FormRequest
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
            'user_id' => [
                'required',
                Rule::exists('users', 'id'),
            ],
            'shift_id' => [
                'required',
                Rule::exists('shifts', 'id'),
                function ($attribute, $value, $fail) {
                    $shiftDate = $this->input('shift_date');
                    if (! $shiftDate) {
                        return;
                    }

                    $dayOfWeek = Carbon::parse($shiftDate)->dayOfWeekIso;

                    $shift = Shift::find($value);

                    if ($shift && $shift->day_of_week !== $dayOfWeek) {
                        $fail(__('messages.shift_date_must_match_shift_day_of_week'));
                    }
                },
            ],
            'shift_date' => [
                'required',
                'date_format:Y-m-d',
                Rule::unique('user_shifts', 'shift_date')
                    ->where('user_id', $this->input('user_id')),
            ],
            'machine_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::exists('machines', 'code')->where('status', MachineStatus::ACTIVE->value),
            ],
        ];
    }
}
