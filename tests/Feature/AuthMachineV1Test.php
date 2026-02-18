<?php

use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('login success', function () {
    // Travel to Feb 17, 2026 at 10:00 (Shift Pagi: 07:00-15:00)
    // Index: (17-1) % 4 = 0 -> Shift Pagi
    $this->travelTo(now()->setDate(2026, 2, 17)->setTime(10, 0, 0));

    $shiftId = Shift::query()
        ->where('day_of_week', now()->dayOfWeekIso)
        ->where('start_time', '<=', now()->toTimeString())
        ->where('end_time', '>=', now()->addHours(1)->toTimeString())
        ->value('id');

    UserShift::updateOrCreate([
        'user_id' => User::where('employee_number', '000001')->value('id'),
        'machine_code' => 'FILLING-MACHINE-001',
        'shift_date' => now()->format('Y-m-d'),
    ], [
        'shift_id' => $shiftId
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

test('login no shift', function () {
    // Travel to Feb 17, 2026 at 10:00 (same as login success test)
    $this->travelTo(now()->setDate(2026, 2, 17)->setTime(10, 0, 0));

    UserShift::where('user_id', User::where('employee_number', '000001')->value('id'))
        ->whereDate('shift_date', now()->format('Y-m-d'))
        ->delete();

    $response = $this->post('api/machine/v1/auth/login', [
        'pin' => '000001',
        'machine_code' => 'FILLING-MACHINE-001',
    ]);

    $response->assertStatus(403);
    $response->assertJsonStructure(['message']);
});

test('login shift out of range', function () {
    // Travel to Feb 17, 2026 at 05:00 (outside all shift hours)
    $this->travelTo(now()->setDate(2026, 2, 17)->setTime(5, 0, 0));

    $shiftId = Shift::query()
        ->where('day_of_week', now()->dayOfWeekIso)
        ->where('start_time', '>=', now()->toTimeString())
        ->value('id');

    UserShift::updateOrCreate([
        'user_id' => User::where('employee_number', '000001')->value('id'),
        'machine_code' => 'FILLING-MACHINE-001',
        'shift_date' => now()->format('Y-m-d'),
    ], [
        'shift_id' => $shiftId
    ]);

    $response = $this->post('api/machine/v1/auth/login', [
        'pin' => '000001',
        'machine_code' => 'FILLING-MACHINE-001',
    ]);

    $response->assertStatus(403);
    $response->assertJsonStructure(['message']);
    $response->assertJsonFragment(['message' => 'login gagal pada ' . now()->format('d-m-Y H:i:s') . ', di luar jam kerja shift. Shift mulai pukul ' . Shift::find($shiftId)->start_time . ' sampai ' . Shift::find($shiftId)->end_time . '.']);
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
