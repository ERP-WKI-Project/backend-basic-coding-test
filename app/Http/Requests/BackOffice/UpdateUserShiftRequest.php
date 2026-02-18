<?php

namespace App\Http\Requests\BackOffice;

use App\Enums\MachineStatus;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserShiftRequest extends FormRequest
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
        $userShift = $this->route('user_shift');

        $userId = $this->input('user_id') ?? optional($userShift)->user_id;

        return [
            'user_id' => [
                'sometimes',
                Rule::exists('users', 'id'),
            ],
            'shift_id' => [
                'sometimes',
                Rule::exists('shifts', 'id'),
                function ($attribute, $value, $fail) use ($userShift) {
                    $shiftDate = $this->input('shift_date') ?? optional($userShift)->shift_date?->format('Y-m-d');
                    if (! $shiftDate) {
                        return;
                    }

                    $dayOfWeek = Carbon::parse($shiftDate)->dayOfWeekIso;

                    $shift = Shift::find($value ?? optional($userShift)->shift_id);

                    if ($shift && $shift->day_of_week !== $dayOfWeek) {
                        $fail(__('messages.shift_date_must_match_shift_day_of_week'));
                    }
                },
            ],
            'shift_date' => [
                'sometimes',
                'date_format:Y-m-d',
                Rule::unique('user_shifts', 'shift_date')
                    ->ignore($userShift)
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at'),
                function ($attribute, $value, $fail) use ($userShift) {
                    if (! $value) {
                        return;
                    }

                    $dayOfWeek = Carbon::parse($value)->dayOfWeekIso;
                    $shiftId = $this->input('shift_id') ?? optional($userShift)->shift_id;
                    $shift = Shift::find($shiftId);

                    if ($shift && $shift->day_of_week !== $dayOfWeek) {
                        $fail(__('messages.shift_date_must_match_shift_day_of_week'));
                    }
                },
            ],
            'machine_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::exists('machines', 'code')->where('status', MachineStatus::ACTIVE->value),
            ],
        ];
    }
}
