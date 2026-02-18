<?php

namespace App\Http\Requests\BackOffice;

use App\Enums\MachineStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $machineUlid = $this->route('machine');
        $machine = \App\Models\Machine::where('ulid', $machineUlid)->first();
        $machineId = $machine ? $machine->id : null;

        return [
            'code' => ['sometimes', 'string', 'regex:/^[A-Z0-9-]+$/', 'max:50', 'unique:machines,code,'.$machineId],
            'name' => ['sometimes', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', new Enum(MachineStatusEnum::class)],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Kode mesin hanya boleh huruf besar, angka, dan tanda hubung.',
            'code.unique' => 'Kode mesin sudah terdaftar.',
            'status.enum' => 'Status mesin tidak valid.',
        ];
    }
}
