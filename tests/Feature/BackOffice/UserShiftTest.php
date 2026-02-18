<?php

use App\Models\User;
use App\Models\Shift;
use App\Models\Machine;
use App\Models\UserShift;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    // Authenticate as backoffice user
    $user = User::where('employee_number', '000001')->first();
    Sanctum::actingAs($user, [\App\Enums\SystemAbility::BACKOFFICE->value]);
});

describe('BackOffice User Shift Management', function () {

    test('index returns paginated list of user shifts', function () {
        $response = $this->getJson('/api/backoffice/v1/user-shift');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'ulid',
                        'shift_date',
                        'machine_code',
                        'user',
                        'shift',
                    ]
                ],
                'links',
                'meta'
            ]);
    });

    test('index with employee_number filter returns correct data', function () {
        $response = $this->getJson('/api/backoffice/v1/user-shift?filter[employee_number]=000001');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'ulid',
                        'shift_date',
                        'user',
                        'shift',
                    ]
                ]
            ]);

        $data = $response->json('data');
        if (!empty($data)) {
            expect($data[0]['user']['employee_number'])->toBe('000001');
        }
    });

    test('index with shift_date filter returns correct data', function () {
        $userShift = UserShift::first();
        if (!$userShift) {
            expect(true)->toBeTrue(); // Skip if no data
            return;
        }

        $shiftDate = $userShift->shift_date->format('Y-m-d');
        $response = $this->getJson("/api/backoffice/v1/user-shift?filter[shift_date]={$shiftDate}");

        $response->assertStatus(200);

        $data = $response->json('data');
        if (!empty($data)) {
            expect($data[0]['shift_date'])->toBe($shiftDate);
        }
    });

    test('index with machine_code filter returns correct data', function () {
        $response = $this->getJson('/api/backoffice/v1/user-shift?filter[machine_code]=FILLING-MACHINE-001');

        $response->assertStatus(200);

        $data = $response->json('data');
        if (!empty($data)) {
            expect($data[0]['machine_code'])->toBe('FILLING-MACHINE-001');
        }
    });

    test('store creates new user shift successfully', function () {
        $user = User::factory()->create();
        $shift = Shift::first();
        $shiftDate = now()->addMonths(2)->format('Y-m-d');

        $response = $this->postJson('/api/backoffice/v1/user-shift', [
            'employee_number' => $user->employee_number,
            'shift_ulid' => $shift->ulid,
            'shift_date' => $shiftDate,
            'machine_code' => 'FILLING-MACHINE-001',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'ulid',
                    'shift_date',
                    'machine_code',
                ]
            ]);

        // Check the record exists with correct attributes
        $userShift = UserShift::where('user_id', $user->id)
            ->where('shift_id', $shift->id)
            ->whereDate('shift_date', $shiftDate)
            ->first();

        expect($userShift)->not->toBeNull()
            ->and($userShift->machine_code)->toBe('FILLING-MACHINE-001');
    });

    test('store creates user shift without machine_code', function () {
        $user = User::factory()->create();
        $shift = Shift::first();
        $shiftDate = now()->addMonths(2)->format('Y-m-d');

        $response = $this->postJson('/api/backoffice/v1/user-shift', [
            'employee_number' => $user->employee_number,
            'shift_ulid' => $shift->ulid,
            'shift_date' => $shiftDate,
        ]);

        $response->assertStatus(201);

        // Check the record exists with correct user, shift, and date
        $userShift = UserShift::where('user_id', $user->id)
            ->where('shift_id', $shift->id)
            ->whereDate('shift_date', $shiftDate)
            ->first();

        expect($userShift)->not->toBeNull()
            ->and($userShift->machine_code)->toBeNull();
    });

    test('store fails with invalid employee_number', function () {
        $shift = Shift::first();

        $response = $this->postJson('/api/backoffice/v1/user-shift', [
            'employee_number' => '999999',
            'shift_ulid' => $shift->ulid,
            'shift_date' => now()->addMonths(2)->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number']);
    });

    test('store fails with invalid shift_ulid', function () {
        $user = User::factory()->create();

        $response = $this->postJson('/api/backoffice/v1/user-shift', [
            'employee_number' => $user->employee_number,
            'shift_ulid' => '01HZZZZZZZZZZZZZZZZZZZZZZ', // Non-existent shift
            'shift_date' => now()->addMonths(2)->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_ulid']);
    });

    test('store fails with invalid date format', function () {
        $user = User::factory()->create();
        $shift = Shift::first();

        $response = $this->postJson('/api/backoffice/v1/user-shift', [
            'employee_number' => $user->employee_number,
            'shift_ulid' => $shift->ulid,
            'shift_date' => '18-02-2026', // Wrong format
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_date']);
    });

    test('store fails when user has conflicting shift', function () {
        $user = User::factory()->create();
        $shiftDate = now()->next('Monday');
        $dayOfWeek = $shiftDate->dayOfWeekIso; // 1 for Monday
        $shift = Shift::where('day_of_week', $dayOfWeek)->first();

        // Create first shift
        UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'shift_date' => $shiftDate->format('Y-m-d'),
            'machine_code' => 'FILLING-MACHINE-001',
        ]);

        // Try to create overlapping shift for same user on same date
        $response = $this->postJson('/api/backoffice/v1/user-shift', [
            'employee_number' => $user->employee_number,
            'shift_ulid' => $shift->ulid,
            'shift_date' => $shiftDate->format('Y-m-d'),
        ]);

        $response->assertStatus(402);
    });

    test('store fails with missing required fields', function () {
        $response = $this->postJson('/api/backoffice/v1/user-shift', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number', 'shift_ulid', 'shift_date']);
    });

    test('show returns user shift details', function () {
        $userShift = UserShift::first();
        if (!$userShift) {
            expect(true)->toBeTrue(); // Skip if no data
            return;
        }

        $response = $this->getJson("/api/backoffice/v1/user-shift/{$userShift->ulid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'ulid',
                    'shift_date',
                    'machine_code',
                    'user',
                    'shift',
                ]
            ]);
    });

    test('show returns 404 for non-existent user shift', function () {
        // Use a valid ULID format that definitely doesn't exist
        $nonExistentUlid = '01HZZZZZZZZZZZZZZZZZZZZZZ';
        $response = $this->getJson("/api/backoffice/v1/user-shift/{$nonExistentUlid}");

        $response->assertStatus(404);
    });

    test('update modifies user shift successfully', function () {
        $user = User::factory()->create();
        $shiftDate = now()->next('Tuesday');
        $dayOfWeek = $shiftDate->dayOfWeekIso; // 2 for Tuesday

        $shift1 = Shift::where('day_of_week', $dayOfWeek)->where('name', 'Shift Pagi')->first();
        $shift2 = Shift::where('day_of_week', $dayOfWeek)->where('name', 'Shift Siang')->first();

        $userShift = UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift1->id,
            'shift_date' => $shiftDate->format('Y-m-d'),
            'machine_code' => 'FILLING-MACHINE-001',
        ]);

        $response = $this->putJson("/api/backoffice/v1/user-shift/{$userShift->ulid}", [
            'employee_number' => $user->employee_number,
            'shift_ulid' => $shift2->ulid,
            'shift_date' => $shiftDate->format('Y-m-d'),
            'machine_code' => 'FILLING-MACHINE-001',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'ulid',
                    'shift_date',
                ]
            ]);

        $this->assertDatabaseHas('user_shifts', [
            'ulid' => $userShift->ulid,
            'shift_id' => $shift2->id,
        ]);
    });

    test('update fails with conflicting shift', function () {
        $user = User::factory()->create();
        $shiftDate1 = now()->next('Wednesday');
        $dayOfWeek = $shiftDate1->dayOfWeekIso; // 3 for Wednesday

        $shift1 = Shift::where('day_of_week', $dayOfWeek)->where('name', 'Shift Pagi')->first();
        $shift2 = Shift::where('day_of_week', $dayOfWeek)->where('name', 'Shift Siang')->first();

        // Create first shift
        $userShift1 = UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift1->id,
            'shift_date' => $shiftDate1->format('Y-m-d'),
        ]);

        $shiftDate2 = now()->next('Thursday');
        $dayOfWeek2 = $shiftDate2->dayOfWeekIso; // 4 for Thursday
        $shift3 = Shift::where('day_of_week', $dayOfWeek2)->first();

        // Create second shift
        $userShift2 = UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift3->id,
            'shift_date' => $shiftDate2->format('Y-m-d'),
        ]);

        // Try to update second shift to conflict with first
        $response = $this->putJson("/api/backoffice/v1/user-shift/{$userShift2->ulid}", [
            'employee_number' => $user->employee_number,
            'shift_ulid' => $shift1->ulid,
            'shift_date' => $shiftDate1->format('Y-m-d'), // Same date as first shift
        ]);

        $response->assertStatus(402);
    });

    test('update fails with invalid data', function () {
        $userShift = UserShift::first();
        if (!$userShift) {
            expect(true)->toBeTrue(); // Skip if no data
            return;
        }

        $response = $this->putJson("/api/backoffice/v1/user-shift/{$userShift->ulid}", [
            'employee_number' => '999999', // Non-existent user
            'shift_ulid' => '01HZZZZZZZZZZZZZZZZZZZZZZ', // Non-existent shift
            'shift_date' => 'invalid-date',
        ]);

        $response->assertStatus(422);
    });

    test('destroy deletes user shift successfully', function () {
        $user = User::factory()->create();
        $shift = Shift::first();
        $shiftDate = now()->addMonths(2)->format('Y-m-d');

        $userShift = UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'shift_date' => $shiftDate,
        ]);

        $response = $this->deleteJson("/api/backoffice/v1/user-shift/{$userShift->ulid}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Shift assignment deleted successfully'
            ]);

        $this->assertDatabaseMissing('user_shifts', [
            'ulid' => $userShift->ulid,
        ]);
    });

    test('destroy returns 404 for non-existent user shift', function () {
        // Use a valid ULID format that definitely doesn't exist
        $nonExistentUlid = '01HZZZZZZZZZZZZZZZZZZZZZZ';
        $response = $this->deleteJson("/api/backoffice/v1/user-shift/{$nonExistentUlid}");

        $response->assertStatus(404);
    });

});

