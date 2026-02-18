<?php

use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Machine;

beforeEach(function () {
    $this->service = new \App\Services\MachineService();
});

test('it can get all machine paginated', function () {
    Machine::factory()->count(15)->create();

    $result = $this->service->getAll(10);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(15)
        ->and($result->perPage())->toBe(10);
});

test('it can find a machine by id', function () {
    $mc = Machine::factory()->create();

    $found = $this->service->findById($mc->id);

    expect($found->id)->toBe($mc->id)
        ->and($found->machine_code)->toBe($mc->machine_code);
});

test('it throws exception if machine not found by id', function () {
    $this->service->findById(999);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('it can create a machine', function () {
    $dto = new \App\DTOs\MachineDto(
        machine_code: 'FILLING-MACHINE-200',
        description: 'desc',
    );

    // act
    $mc = $this->service->create($dto);

    // assert
    expect($mc->machine_code)->toBe('FILLING-MACHINE-200');

    $this->assertDatabaseHas('machines', [
        'machine_code' => 'FILLING-MACHINE-200'
    ]);
});

test('it can update machine', function () {
    $mc = Machine::factory()->create();

    $dto = new \App\DTOs\MachineDto(
        machine_code: 'FILLING-MACHINE-200',
        description: 'desc updated',
    );

    // act
    $updatedMc = $this->service->update($mc->id, $dto);

    // assert
    expect($updatedMc->description)->toBe('desc updated');
});

test('it can delete a machine', function () {
    $mc = Machine::factory()->create();

    $this->service->delete($mc->id);

    $this->assertDatabaseMissing('machines', ['id' => $mc->id]);
});
