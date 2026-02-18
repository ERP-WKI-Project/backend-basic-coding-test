<?php

namespace App\Http\Requests\BackOffice\Machine;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Machine\MachineStatus;
use Illuminate\Validation\Rule;

class UpdateMachineRequest extends FormRequest
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
        $machine = $this->route('machine');
        $machineId = $machine->id ?? $machine;

        return [
            'code' => [
                'required',
                'string',
                'unique:machines,code,' . $machineId,
                function ($attribute, $value, $fail) use ($machine) {
                    if ($value !== $machine->code) {
                        $hasLogs = $machine->machineLogs()->exists();
                        $hasShifts = $machine->userShifts()->exists();

                        if ($hasLogs || $hasShifts) {
                            $fail('The machine code cannot be changed because this machine already has transaction logs or shift assignments.');
                        }
                    }
                },
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', Rule::enum(MachineStatus::class)],
        ];
    }
}
