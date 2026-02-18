<?php

use App\Models\MachineLog;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    $this->service = new \App\Services\UserMachineActivityService();
});

test('it can filter machine logs by date range', function () {
    // 1. Create logs on specific dates
    // Date format must match what you pass to the Service
    MachineLog::factory()->create(['created_at' => '2023-01-01 10:00:00']); // Target
    MachineLog::factory()->create(['created_at' => '2023-01-05 12:00:00']); // Target
    MachineLog::factory()->create(['created_at' => '2023-01-10 15:00:00']); // Outside (Too late)
    MachineLog::factory()->create(['created_at' => '2022-12-31 23:59:59']); // Outside (Too early)

    // 2. Act
    $result = $this->service->getByRangeDate('2023-01-01', '2023-01-06');

    // 3. Assert
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(2);
});

test('it orders the results by created_at ascending', function () {
    // 1. Create logs in scrambled order
    MachineLog::factory()->create(['created_at' => '2023-01-03']);
    MachineLog::factory()->create(['created_at' => '2023-01-01']);
    MachineLog::factory()->create(['created_at' => '2023-01-02']);

    // 2. Act
    $result = $this->service->getByRangeDate('2023-01-01', '2023-01-05');

    // 3. Assert (Check order)
    expect($result->items()[0]->created_at->format('Y-m-d'))->toBe('2023-01-01')
        ->and($result->items()[1]->created_at->format('Y-m-d'))->toBe('2023-01-02')
        ->and($result->items()[2]->created_at->format('Y-m-d'))->toBe('2023-01-03');
});

test('it returns an empty paginator if no logs found in range', function () {
    MachineLog::factory()->create(['created_at' => '2023-05-01']);

    $result = $this->service->getByRangeDate('2023-01-01', '2023-01-31');

    expect($result->total())->toBe(0);
});

test('it respects the perPage parameter', function () {
    MachineLog::factory()->count(15)->create(['created_at' => '2023-01-01']);

    $result = $this->service->getByRangeDate('2023-01-01', '2023-01-01', 5);

    expect($result->perPage())->toBe(5)
        ->and($result->total())->toBe(15);
});
