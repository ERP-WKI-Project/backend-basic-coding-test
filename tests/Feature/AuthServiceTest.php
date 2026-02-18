<?php

use App\Services\AuthService;
use App\DTOs\AuthCredentialDto;
use App\DTOs\AuthDto;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

beforeEach(function () {
    Queue::fake();

    // Create a mock user that behaves like an Eloquent model
    $this->user = mock(User::class)->makePartial();
    $this->user->employee_number = 'EMP-001';
});

/**
 * --- authenticateBackOffice ---
 */

test('backoffice login returns success dto with token on valid password', function () {
    $this->user->password = 'hashed_secret';

    Hash::shouldReceive('check')
        ->once()
        ->with('plain_password', 'hashed_secret')
        ->andReturn(true);

    // Create a dummy object to represent the NewAccessToken
    $tokenResult = (object) ['plainTextToken' => 'fake_sanctum_token'];

    // Mock createToken to return that object
    $this->user->shouldReceive('createToken')
        ->once()
        ->with('auth_token', Mockery::any()) // Mockery::any() covers the abilities array
        ->andReturn($tokenResult);

    $dto = new AuthCredentialDto(user: $this->user, password: 'plain_password');

    $result = AuthService::authenticateBackOffice($dto);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->token)->toBe('fake_sanctum_token');
});

test('backoffice login returns failure dto on invalid password', function () {
    $this->user->password = 'hashed_secret';

    Hash::shouldReceive('check')->andReturn(false);

    $dto = new AuthCredentialDto(user: $this->user, password: 'wrong_password');

    $result = AuthService::authenticateBackOffice($dto);

    expect($result->isSuccess())->toBeFalse()
        ->and($result->token)->toBeNull()
        ->and($result->errorMessage)->toContain('tidak sesuai');
});

/**
 * --- authenticateUseMachine ---
 */

test('machine login fails when no shift exists for today', function () {
    // Mock the relationship chain: $user->userShifts()->with()->where()->whereDate()->first()
    $userShiftsMock = mock();
    $this->user->shouldReceive('userShifts')->andReturn($userShiftsMock);

    $userShiftsMock->shouldReceive('with->where->whereDate->first')
        ->andReturn(null);

    $dto = new AuthCredentialDto(user: $this->user, machineCode: 'MC-01');

    $result = AuthService::authenticateUseMachine($dto);

    expect($result->isSuccess())->toBeFalse()
        ->and($result->errorMessage)->toContain('tidak memiliki shift');
});

test('machine login fails when outside of shift hours', function () {
    // Setup a mock shift object
    $mockShift = (object) ['start_time' => '08:00:00', 'end_time' => '17:00:00'];
    $mockUserShift = (object) ['shift' => $mockShift];

    $userShiftsMock = mock();
    $this->user->shouldReceive('userShifts')->andReturn($userShiftsMock);
    $userShiftsMock->shouldReceive('with->where->whereDate->first')
        ->andReturn($mockUserShift);

    // Set "now" to 19:00:00 (Outside 08:00 - 17:00)
    Carbon::setTestNow(Carbon::createFromTimeString('19:00:00'));

    $dto = new AuthCredentialDto(user: $this->user, machineCode: 'MC-01');
    $result = AuthService::authenticateUseMachine($dto);

    expect($result->isSuccess())->toBeFalse()
        ->and($result->errorMessage)->toContain('di luar jam kerja shift');
});

test('machine login succeeds within shift hours', function () {
    // Setup Mock Shift
    $mockShift = (object) ['start_time' => '08:00:00', 'end_time' => '17:00:00'];
    $mockUserShift = (object) ['shift' => $mockShift];

    // Mock the relationship chain
    $userShiftsMock = mock();
    $this->user->shouldReceive('userShifts')->andReturn($userShiftsMock);
    $userShiftsMock->shouldReceive('with->where->whereDate->first')->andReturn($mockUserShift);

    // FIX: Return an object with the public property
    $this->user->shouldReceive('createToken')
        ->andReturn((object) ['plainTextToken' => 'fake_machine_token']);

    // Set time to 10 AM (Inside 08:00 - 17:00)
    Carbon::setTestNow(Carbon::parse('10:00:00'));

    $dto = new AuthCredentialDto(user: $this->user, machineCode: 'MC-01');
    $result = AuthService::authenticateUseMachine($dto);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->token)->toBe('fake_machine_token');
});
