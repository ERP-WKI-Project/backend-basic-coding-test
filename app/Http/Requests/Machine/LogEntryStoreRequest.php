<?php

namespace App\Http\Requests\Machine;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\MachineLog\EventEnum;

class LogEntryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eventValues = array_map(fn($c) => $c->value, EventEnum::cases());

        return [
            'machine_code' => 'required|string|max:50',
            'event' => 'required|string|in:' . implode(',', $eventValues),
            'log_message' => 'required|string',
        ];
    }
}
