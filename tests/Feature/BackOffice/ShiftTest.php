<?php

use App\Models\User;
use App\Models\Machine;
use App\Models\Shift;
use App\Models\UserShift;
use App\Enums\Machine\MachineStatus;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'employee_number' => '000001',
        'password' => bcrypt('password'),
    ]);

    // Create a standard shift for testing (e.g., Monday 08:00-16:00)
    $this->shift = Shift::create([
        'name' => 'Morning Shift',
        'day_of_week' => 1, // Monday
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
    ]);

    $this->machine = Machine::factory()->create([
        'code' => 'MCH-001',
        'status' => MachineStatus::ACTIVE->value,
    ]);

    $this->targetUser = User::factory()->create();
});

test('unauthenticated user cannot access shift endpoints', function () {
    $this->getJson(route('api.backoffice.v1.shift.index'))->assertStatus(401);
    $this->postJson(route('api.backoffice.v1.shift.store'), [])->assertStatus(401);

    // Create a user shift for update/delete tests
    $userShift = UserShift::create([
        'user_id' => $this->targetUser->id,
        'shift_id' => $this->shift->id,
        'machine_id' => $this->machine->id,
        'machine_code' => $this->machine->code,
        'shift_date' => Carbon::now()->next(1)->toDateString(), // Next Monday
        'created_by' => $this->user->id,
    ]);

    $this->putJson(route('api.backoffice.v1.shift.update', $userShift), [])->assertStatus(401);
    $this->deleteJson(route('api.backoffice.v1.shift.destroy', $userShift))->assertStatus(401);
});

test('admin can list user shifts with pagination', function () {
    // Create some user shifts
    $nextMonday = Carbon::now()->next(1)->toDateString();

    UserShift::create([
        'user_id' => $this->targetUser->id,
        'shift_id' => $this->shift->id,
        'machine_id' => $this->machine->id,
        'machine_code' => $this->machine->code,
        'shift_date' => $nextMonday,
        'created_by' => $this->user->id,
    ]);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->getJson(route('api.backoffice.v1.shift.index'));

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonCount(1, 'data');
});

test('admin can assign a shift to a user', function () {
    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $nextMonday = Carbon::now()->next(1)->toDateString();

    $data = [
        'user_id' => $this->targetUser->ulid,
        'shift_id' => $this->shift->ulid,
        'machine_id' => $this->machine->ulid,
        'shift_date' => $nextMonday,
    ];

    $response = $this->postJson(route('api.backoffice.v1.shift.store'), $data);

    $response->assertStatus(201)
        ->assertJsonFragment(['shift_date' => $nextMonday]);

    $this->assertDatabaseHas('user_shifts', [
        'user_id' => $this->targetUser->id,
        'shift_id' => $this->shift->id,
        'machine_id' => $this->machine->id,
        'shift_date' => $nextMonday,
    ]);
});

test('admin cannot assign shift if fields are missing or invalid', function () {
    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    // Missing fields
    $this->postJson(route('api.backoffice.v1.shift.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['user_id', 'shift_id', 'machine_id', 'shift_date']);

    // Invalid ULIDs
    $this->postJson(route('api.backoffice.v1.shift.store'), [
        'user_id' => 'INVALID-ULID',
        'shift_id' => 'INVALID-ULID',
        'machine_id' => 'INVALID-ULID',
        'shift_date' => 'not-a-date',
    ])->assertStatus(422);
});

test('admin cannot assign shift if date does not match shift day of week', function () {
    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    // Our shift is Monday (day_of_week = 1)
    // Try to assign on a Tuesday
    $nextTuesday = Carbon::now()->next(2)->toDateString();

    $data = [
        'user_id' => $this->targetUser->ulid,
        'shift_id' => $this->shift->ulid,
        'machine_id' => $this->machine->ulid,
        'shift_date' => $nextTuesday,
    ];

    $response = $this->postJson(route('api.backoffice.v1.shift.store'), $data);

    $response->assertStatus(422)
        // The error message is customized in service: "The selected shift does not match the day of the week for this date."
        // Assuming default error response structure or checking for 422 is enough
        ->assertJsonFragment(['message' => 'Failed to create shift: The selected shift does not match the day of the week for this date.']);
});

test('admin cannot assign shift if user is already busy', function () {
    $nextMonday = Carbon::now()->next(1)->toDateString();

    // Assign first shift
    UserShift::create([
        'user_id' => $this->targetUser->id,
        'shift_id' => $this->shift->id,
        'machine_id' => $this->machine->id,
        'machine_code' => $this->machine->code,
        'shift_date' => $nextMonday,
        'created_by' => $this->user->id,
    ]);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    // Try to assign same user to another machine same day same shift (or any shift on same day based on logic)
    // The logic `validateUserAvailability` checks if user has ANY shift on that date.

    $otherMachine = Machine::factory()->create(['code' => 'MCH-002']);

    $data = [
        'user_id' => $this->targetUser->ulid,
        'shift_id' => $this->shift->ulid,
        'machine_id' => $otherMachine->ulid,
        'shift_date' => $nextMonday,
    ];

    $response = $this->postJson(route('api.backoffice.v1.shift.store'), $data);

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => "Failed to create shift: Employee already has a shift on {$nextMonday}."]);
});

test('admin cannot assign shift if machine is already busy', function () {
    $nextMonday = Carbon::now()->next(1)->toDateString();

    // Assign machine to another user
    $otherUser = User::factory()->create();
    UserShift::create([
        'user_id' => $otherUser->id,
        'shift_id' => $this->shift->id,
        'machine_id' => $this->machine->id,
        'machine_code' => $this->machine->code,
        'shift_date' => $nextMonday,
        'created_by' => $this->user->id,
    ]);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    // Try to assign same machine to targetUser
    $data = [
        'user_id' => $this->targetUser->ulid,
        'shift_id' => $this->shift->ulid,
        'machine_id' => $this->machine->ulid,
        'shift_date' => $nextMonday,
    ];

    $response = $this->postJson(route('api.backoffice.v1.shift.store'), $data);

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => "Failed to create shift: This machine is already assigned to another employee for this shift."]);
});

test('admin can update a shift assignment', function () {
    $nextMonday = Carbon::now()->next(1)->toDateString();

    $userShift = UserShift::create([
        'user_id' => $this->targetUser->id, // Initial user
        'shift_id' => $this->shift->id,
        'machine_id' => $this->machine->id,
        'machine_code' => $this->machine->code,
        'shift_date' => $nextMonday,
        'created_by' => $this->user->id,
    ]);

    $otherUser = User::factory()->create();

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    // Change user
    $data = [
        'user_id' => $otherUser->ulid,
        'shift_id' => $this->shift->ulid,
        'machine_id' => $this->machine->ulid,
        'shift_date' => $nextMonday,
    ];

    $response = $this->putJson(route('api.backoffice.v1.shift.update', $userShift), $data);

    $response->assertStatus(200);

    $this->assertDatabaseHas('user_shifts', [
        'id' => $userShift->id,
        'user_id' => $otherUser->id,
    ]);
});

test('admin can delete a future shift assignment', function () {
    $nextMonday = Carbon::now()->next(1)->toDateString();

    $userShift = UserShift::create([
        'user_id' => $this->targetUser->id,
        'shift_id' => $this->shift->id,
        'machine_id' => $this->machine->id,
        'machine_code' => $this->machine->code,
        'shift_date' => $nextMonday,  // Future date
        'created_by' => $this->user->id,
    ]);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->deleteJson(route('api.backoffice.v1.shift.destroy', $userShift));

    $response->assertStatus(200);

    $this->assertSoftDeleted('user_shifts', ['id' => $userShift->id]);
});

test('admin CANNOT delete a past shift assignment', function () {
    $lastMonday = Carbon::now()->subWeeks(1)->startOfWeek()->toDateString();

    // We force create a past shift (validation usually prevents creating past shifts via API, but DB allows it)
    $userShift = UserShift::create([
        'user_id' => $this->targetUser->id,
        'shift_id' => $this->shift->id,
        'machine_id' => $this->machine->id,
        'machine_code' => $this->machine->code,
        'shift_date' => $lastMonday, // Past date
        'created_by' => $this->user->id,
    ]);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->deleteJson(route('api.backoffice.v1.shift.destroy', $userShift));

    $response->assertStatus(409) // 409 Conflict is often used for logic errors, controller returns 409 on catch
        ->assertJsonFragment(['message' => 'Failed to delete shift: Cannot delete a shift assignment that has already passed.']);
});

test('admin can filter user shifts by machine', function () {
    $nextMonday = Carbon::now()->next(1)->toDateString();

    UserShift::create([
        'user_id' => $this->targetUser->id,
        'shift_id' => $this->shift->id,
        'machine_id' => $this->machine->id, // MCH-001
        'machine_code' => $this->machine->code,
        'shift_date' => $nextMonday,
        'created_by' => $this->user->id,
    ]);

    $otherMachine = Machine::factory()->create(['code' => 'MCH-002']);
    UserShift::create([
        'user_id' => $this->targetUser->id,
        'shift_id' => $this->shift->id,
        'machine_id' => $otherMachine->id, // MCH-002
        'machine_code' => $otherMachine->code,
        'shift_date' => Carbon::now()->next(1)->addWeek()->toDateString(),
        'created_by' => $this->user->id,
    ]);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    // Filter by MCH-001
    $ulid = $this->machine->ulid;
    // $url = route('api.backoffice.v1.shift.index', ['machine_id' => $ulid]);
    // dump('ULID: ' . $ulid);
    // dump('URL: ' . $url);

    // $response = $this->getJson($url);

    $response = $this->json('GET', route('api.backoffice.v1.shift.index'), ['machine_id' => $ulid]);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');

    // Verify the returned shift belongs to the correct machine
    // Note: The structure is data[0]['machine']['code'] based on UserShiftResource
    $response->assertJsonPath('data.0.machine.code', 'MCH-001');
});

test('admin can filter user shifts by date', function () {
    $date1 = Carbon::now()->next(1)->toDateString();
    $date2 = Carbon::now()->next(1)->addWeek()->toDateString();

    // Create shift for date1
    UserShift::create([
        'user_id' => $this->targetUser->id,
        'shift_id' => $this->shift->id,
        'machine_id' => $this->machine->id,
        'machine_code' => $this->machine->code,
        'shift_date' => $date1,
        'created_by' => $this->user->id,
    ]);

    // Create shift for date2
    UserShift::create([
        'user_id' => $this->targetUser->id,
        'shift_id' => $this->shift->id,
        'machine_id' => $this->machine->id,
        'machine_code' => $this->machine->code,
        'shift_date' => $date2,
        'created_by' => $this->user->id,
    ]);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    // Filter by date1
    $response = $this->json('GET', route('api.backoffice.v1.shift.index'), ['date' => $date1]);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.shift_date', $date1);
});
