<?php

namespace App\Http\Controllers\Machine;

use App\Http\Controllers\Controller;
use App\Http\Requests\Machine\LogEntryRequest;
use App\Http\Resources\Machine\LogEntryResource;
use App\Models\MachineLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LogEntryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $userShift = auth()->user()->userShifts()
            ->whereDate('shift_date', now()->format('Y-m-d'))
            ->first();

        $machineCode = $userShift?->machine_code ?? 'unknown';

        $logs = MachineLog::where('machine_code', $machineCode)
            ->orderByDesc('created_at')
            ->paginate(20);

        return LogEntryResource::collection($logs);
    }

    public function store(LogEntryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $type = $validated['type'];
        $description = $validated['description'] ?? null;
        $machineCode = $validated['machine_code'];

        $messages = [
            'in' => "User {$request->user()->name} (PIN: {$request->user()->employee_number}) melakukan check-in pada mesin {$machineCode}.",
            'out' => "User {$request->user()->name} (PIN: {$request->user()->employee_number}) melakukan check-out pada mesin {$machineCode}.",
            'maintenance' => "Maintenance log oleh {$request->user()->name} (PIN: {$request->user()->employee_number}) pada mesin {$machineCode}. " . ($description ? "Keterangan: {$description}" : ''),
            'issue' => "Issue report oleh {$request->user()->name} (PIN: {$request->user()->employee_number}) pada mesin {$machineCode}. " . ($description ? "Keterangan: {$description}" : ''),
        ];

        $log = MachineLog::create([
            'user_id' => $request->user()->id,
            'machine_code' => $machineCode,
            'event' => $type,
            'log_message' => $messages[$type] ?? "Log entry: {$type}",
        ]);

        return $this->created(new LogEntryResource($log), 'Log entry berhasil disimpan.');
    }
}
