<?php

use App\Models\User;
use App\Models\Machine;
use App\Models\Shift;
use App\Models\UserShift;
use App\Models\MachineLog;
use App\Enums\Machine\MachineStatus;
use App\Enums\MachineLog\EventEnum;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessMachineLog;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Freeze time to a Monday at 12:00:00 to avoid weekend/time issues
    Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00'));

    $this->user = User::factory()->create([
        'employee_number' => '000001',
        'password' => bcrypt('password'),
    ]);

    $this->machine = Machine::factory()->create([
        'code' => 'MCH-001',
        'status' => MachineStatus::ACTIVE->value,
    ]);

    // Create a shift that covers the current time (08:00 to 16:00)
    $this->shift = Shift::create([
        'name' => 'Current Shift',
        'day_of_week' => 1, // Monday
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
    ]);

    // Assign user to this shift and machine for today
    $this->userShift = UserShift::create([
        'user_id' => $this->user->id,
        'shift_id' => $this->shift->id,
        'machine_id' => $this->machine->id,
        'machine_code' => $this->machine->code,
        'shift_date' => now()->toDateString(),
        'created_by' => $this->user->id,
    ]);
});

test('unauthenticated user cannot access log entry endpoints', function () {
    $this->getJson(route('api.machine.v1.log-entry.index'))->assertStatus(401);
    $this->postJson(route('api.machine.v1.log-entry.store'), [])->assertStatus(401);
});

test('user without active shift cannot list logs', function () {
    // Authenticate a different user
    $otherUser = User::factory()->create();
    Sanctum::actingAs($otherUser, [\App\Enums\SystemAbility::MACHINE->value]);

    $response = $this->getJson(route('api.machine.v1.log-entry.index'));

    $response->assertStatus(403)
        ->assertJsonFragment(['message' => 'You do not have an active shift assignment for this time.']);
});

test('user with active shift can list logs', function () {
    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::MACHINE->value]);

    // Create some logs
    MachineLog::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'machine_id' => $this->machine->id,
        'user_shift_id' => $this->userShift->id,
        'machine_code' => $this->machine->code,
        'event' => EventEnum::JOB_START->value,
    ]);

    $response = $this->getJson(route('api.machine.v1.log-entry.index'));

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('user must be logged into machine to store log', function () {
    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::MACHINE->value]);

    // Ensure NO Login log exists (implied by fresh DB state in beforeEach)

    $data = [
        'event' => EventEnum::JOB_START->value,
        'log_message' => 'Machine running',
    ];

    $response = $this->postJson(route('api.machine.v1.log-entry.store'), $data);

    $response->assertStatus(422)
        // Controller returns 422 for Exception messages in catch block
        ->assertJsonFragment(['message' => 'Unauthorized: You must login to this machine before recording activities.']);
});

test('user with active session can store log and dispatches job', function () {
    Queue::fake();

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::MACHINE->value]);

    // Create Login log to simulate active session
    MachineLog::create([
        'user_id' => $this->user->id,
        'machine_id' => $this->machine->id,
        'user_shift_id' => $this->userShift->id,
        'machine_code' => $this->machine->code,
        'event' => EventEnum::LOGIN_SUCCESS->value,
        'log_message' => 'Login success',
        'created_at' => Carbon::now(),
    ]);

    $data = [
        'event' => EventEnum::JOB_START->value,
        'log_message' => 'Machine running smoothly',
    ];

    $response = $this->postJson(route('api.machine.v1.log-entry.store'), $data);

    $response->assertStatus(200)
        ->assertJsonFragment(['message' => 'Activity logged successfully.']);

    Queue::assertPushed(ProcessMachineLog::class);
});

test('user cannot store log with invalid data', function () {
    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::MACHINE->value]);

    // Even with login, invalid data should fail validation
    MachineLog::create([
        'user_id' => $this->user->id,
        'machine_id' => $this->machine->id,
        'user_shift_id' => $this->userShift->id,
        'machine_code' => $this->machine->code,
        'event' => EventEnum::LOGIN_SUCCESS->value,
        'log_message' => 'Login success',
        'created_at' => Carbon::now(),
    ]);

    $data = [
        'event' => 'INVALID_EVENT',
        'log_message' => '', // Required
    ];

    $response = $this->postJson(route('api.machine.v1.log-entry.store'), $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['event', 'log_message']);
});
