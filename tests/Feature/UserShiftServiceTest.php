<?php

use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\UserShift;
use App\Models\User;
use App\Models\Shift;

beforeEach(function () {
    $this->service = new \App\Services\UserShiftService();
});

test('it can get all user shift paginated', function () {
    UserShift::factory()->count(15)->create();

    $result = $this->service->getAll(10);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(15)
        ->and($result->perPage())->toBe(10);
});

test('it can find a user shift by id', function () {
    $mc = UserShift::factory()->create();

    $found = $this->service->findById($mc->id);

    expect($found->id)->toBe($mc->id)
        ->and($found->user_id)->toBe($mc->user_id)
        ->and($found->shift_id)->toBe($mc->shift_id)
        ->and($found->machine_code)->toBe($mc->machine_code);
});

test('it throws exception if user shift not found by id', function () {
    $this->service->findById(999);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('it can create a user shift', function () {
    // mock
    $user = User::factory()->create(['id' => 99]);
    $shift = Shift::factory()->create(['id' => 77]);

    $dto = new \App\DTOs\UserShiftDto(
        user: $user,
        shift: $shift,
        machineCode: 'FILLING-MACHINE-200',
        shiftDate: '2026-02-20',
    );

    // Act
    $us = $this->service->create($dto);

    // Assert
    expect($us->machine_code)->toBe('FILLING-MACHINE-200')
        ->and($us->shift_date->toDateString())->toBe('2026-02-20');

    $this->assertDatabaseHas('user_shifts', [
        'machine_code' => 'FILLING-MACHINE-200',
        'shift_date' => '2026-02-20 00:00:00'
    ]);
});

test('it can update user shift', function () {
    $us = UserShift::factory()->create();
    $user = User::factory()->create(['id' => 99]);
    $shift = Shift::factory()->create(['id' => 77]);

    $dto = new \App\DTOs\UserShiftDto(
        user: $user,
        shift: $shift,
        machineCode: 'FILLING-MACHINE-200',
        shiftDate: '2026-02-21',
    );

    // act
    $updatedUs = $this->service->update($us->id, $dto);

    // assert
    expect($updatedUs->shift_date->toDateString())->toBe('2026-02-21')
        ->and($updatedUs->machine_code)->toBe('FILLING-MACHINE-200');
});

test('it can delete a user shift', function () {
    $us = UserShift::factory()->create();

    $this->service->delete($us->id);

    $this->assertDatabaseMissing('user_shifts', ['id' => $us->id]);
});
