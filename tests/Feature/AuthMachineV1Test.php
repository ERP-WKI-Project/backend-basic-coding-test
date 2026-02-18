<?php

use App\Models\Machine;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('login_success', function () {
    $user = User::where('employee_number', '000001')->first();
    $machine = Machine::where('code', 'FILLING-MACHINE-001')->first();

    $shiftId = Shift::query()
        ->where('day_of_week', now()->dayOfWeekIso)
        ->where('start_time', '<=', now()->toTimeString())
        ->where('end_time', '>=', now()->addHours(1)->toTimeString())
        ->value('id');

    UserShift::updateOrCreate([
        'user_id' => $user->id,
        'machine_id' => $machine->id,
        'shift_date' => now()->format('Y-m-d'),
    ], [
        'shift_id' => $shiftId,
    ]);

    $response = $this->post('api/machine/v1/auth/login', [
        'pin' => '000001',
        'machine_code' => 'FILLING-MACHINE-001',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'access_token',
        'token_type',
    ]);
});

test('login_no_shift', function () {
    $user = User::where('employee_number', '000001')->first();

    UserShift::where('user_id', $user->id)
        ->whereDate('shift_date', now()->format('Y-m-d'))
        ->delete();

    $response = $this->post('api/machine/v1/auth/login', [
        'pin' => '000001',
        'machine_code' => 'FILLING-MACHINE-001',
    ]);

    $response->assertStatus(403);
    $response->assertJsonStructure(['message']);
});

test('login_shift_out_of_range', function () {
    $user = User::where('employee_number', '000001')->first();
    $machine = Machine::where('code', 'FILLING-MACHINE-001')->first();

    // Delete all existing shifts for today first
    UserShift::where('user_id', $user->id)
        ->whereDate('shift_date', now()->format('Y-m-d'))
        ->delete();

    // Create a shift that starts later today (out of range)
    $shift = Shift::factory()->create([
        'day_of_week' => now()->dayOfWeekIso,
        'start_time' => '23:00:00',
        'end_time' => '07:00:00',
    ]);

    UserShift::create([
        'user_id' => $user->id,
        'machine_id' => $machine->id,
        'shift_date' => now()->format('Y-m-d'),
        'shift_id' => $shift->id,
    ]);

    $response = $this->post('api/machine/v1/auth/login', [
        'pin' => '000001',
        'machine_code' => 'FILLING-MACHINE-001',
    ]);

    $response->assertStatus(403);
    $response->assertJsonStructure(['message']);
});

test('profile', function () {
    Sanctum::actingAs(
        User::where('employee_number', '000001')->first(),
        [\App\Enums\SystemAbility::MACHINE->value]
    );

    // Now, access the profile endpoint with the token
    $profileResponse = $this->get('/api/machine/v1/profile');

    $profileResponse->assertStatus(200);
    $profileResponse->assertJsonStructure(['data' => [
        'employee_number',
        'name',
        'email',
    ]]);
});
