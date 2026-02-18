<?php

use App\Services\MachineLogService;
use App\DTOs\MachineLogDto;
use App\Models\MachineLog;
use App\Models\User;
use App\Enums\MachineLog\EventEnum;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

beforeEach(function () {
    $this->user = User::factory()->create(['id' => 99]);
});

test('addLog calls create on MachineLog model with correct data', function () {
    $dto = new MachineLogDto(
        user: $this->user,
        machineCode: 'FILLING-MACHINE-100',
        event: EventEnum::LOGIN_SUCCESS,
        logMessage: 'Test message'
    );

    // Act
    App\Services\MachineLogService::addLog($dto);

    // Assert
    $this->assertDatabaseHas('machine_logs', [
        'user_id' => 99,
        'machine_code' => 'FILLING-MACHINE-100',
        'event' => EventEnum::LOGIN_SUCCESS->value,
        'log_message' => 'Test message',
    ]);
});

test('getAll returns a paginator of machine logs', function () {
    MachineLog::factory()->count(15)->create();

    $perPage = 10;

    // Act
    $result = MachineLogService::getAll($perPage);

    // Assert
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->perPage())->toBe($perPage)
        ->and($result->total())->toBe(15);
});

test('MachineLogDto maps fromAuth correctly for success', function () {
    $credDto = new \App\DTOs\AuthCredentialDto(
        user: $this->user,
        machineCode: 'FILLING-MACHINE-100',
        password: 'any_password'
    );

    $authDto = \App\DTOs\AuthDto::success('fake_token');

    // Act
    $dto = MachineLogDto::fromAuth($credDto, $authDto);

    // Assert
    expect($dto->event)->toBe(App\Enums\MachineLog\EventEnum::LOGIN_SUCCESS)
        ->and($dto->machineCode)->toBe('FILLING-MACHINE-100')
        ->and($dto->logMessage)->toBe('Login successful');
});

test('MachineLogDto maps fromAuth correctly for failure', function () {
    $credDto = new \App\DTOs\AuthCredentialDto(
        user: $this->user,
        machineCode: 'FILLING-MACHINE-100'
    );

    // act
    $authDto = \App\DTOs\AuthDto::failure('Invalid Shift');
    $dto = MachineLogDto::fromAuth($credDto, $authDto);

    // Assert
    expect($dto->event)->toBe(EventEnum::LOGIN_FAILED)
        ->and($dto->logMessage)->toContain('Invalid Shift');
});
