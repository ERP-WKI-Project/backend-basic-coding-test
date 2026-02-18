<?php

use App\Models\User;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\UserShift;
use App\Models\Shift;
use App\Enums\Machine\MachineStatus;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'employee_number' => '000001',
        'password' => bcrypt('password'),
    ]);
});

test('unauthenticated user cannot access machine endpoints', function () {
    $this->getJson(route('api.backoffice.v1.machine.index'))->assertStatus(401);
    $this->postJson(route('api.backoffice.v1.machine.store'), [])->assertStatus(401);

    $machine = Machine::factory()->create();
    $this->putJson(route('api.backoffice.v1.machine.update', $machine), [])->assertStatus(401);
    $this->deleteJson(route('api.backoffice.v1.machine.destroy', $machine))->assertStatus(401);
});

test('admin can list machines with pagination', function () {
    Machine::factory()->count(15)->create();

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->getJson(route('api.backoffice.v1.machine.index'));

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonCount(10, 'data');
});

test('admin can create a machine', function () {
    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $data = [
        'code' => 'MCH-NEW-001',
        'name' => 'New Machine',
        'description' => 'A brand new machine',
        'status' => MachineStatus::ACTIVE->value,
    ];

    $response = $this->postJson(route('api.backoffice.v1.machine.store'), $data);

    $response->assertStatus(201)
        ->assertJsonFragment($data);

    $this->assertDatabaseHas('machines', ['code' => 'MCH-NEW-001']);
});

test('admin cannot create machine with invalid data', function () {
    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    // Required fields
    $this->postJson(route('api.backoffice.v1.machine.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code', 'name', 'status']);

    // Invalid status
    $this->postJson(route('api.backoffice.v1.machine.store'), [
        'code' => 'MCH-INV',
        'name' => 'Invalid Status',
        'status' => 'unknown_status',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('admin cannot create duplicate machine code', function () {
    Machine::factory()->create(['code' => 'MCH-EXISTING']);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->postJson(route('api.backoffice.v1.machine.store'), [
        'code' => 'MCH-EXISTING',
        'name' => 'Should Fail',
        'status' => MachineStatus::ACTIVE->value,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('admin can update a machine', function () {
    $machine = Machine::factory()->create();

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $data = [
        'code' => $machine->code, // Keep same code
        'name' => 'Updated Name',
        'description' => 'Updated Description',
        'status' => MachineStatus::MAINTENANCE->value,
    ];

    $response = $this->putJson(route('api.backoffice.v1.machine.update', $machine), $data);

    $response->assertStatus(200)
        ->assertJsonFragment($data);

    $this->assertDatabaseHas('machines', ['id' => $machine->id, 'name' => 'Updated Name']);
});

test('admin can update machine code if no dependencies exist', function () {
    $machine = Machine::factory()->create(['code' => 'OLD-CODE']);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->putJson(route('api.backoffice.v1.machine.update', $machine), [
        'code' => 'NEW-CODE',
        'name' => 'Renamed Machine',
        'status' => MachineStatus::ACTIVE->value,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('machines', ['id' => $machine->id, 'code' => 'NEW-CODE']);
});

test('admin CANNOT update machine code if it has transaction logs', function () {
    $machine = Machine::factory()->create(['code' => 'LOCKED-CODE']);

    // Manually create MachineLog since factory might not exist
    MachineLog::create([
        'machine_code' => $machine->code,
        'machine_id' => $machine->id,
        'user_id' => $this->user->id,
        'event' => 'TEST_EVENT',
        'log_message' => 'Test Log Message',
        // 'user_shift_id' is nullable
    ]);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->putJson(route('api.backoffice.v1.machine.update', $machine), [
        'code' => 'NEW-CODE', // Try to change code
        'name' => 'Renamed Machine',
        'status' => MachineStatus::ACTIVE->value,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('admin CANNOT update machine code if it has user shifts', function () {
    $machine = Machine::factory()->create(['code' => 'SHIFT-LOCKED']);

    // Manually create Shift first
    $shift = Shift::create([
        'name' => 'Test Shift',
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
    ]);

    // Manually create UserShift
    UserShift::create([
        'user_id' => $this->user->id,
        'shift_id' => $shift->id,
        'shift_date' => now()->toDateString(),
        'machine_code' => $machine->code,
        'machine_id' => $machine->id,
        'created_by' => $this->user->id,
    ]);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->putJson(route('api.backoffice.v1.machine.update', $machine), [
        'code' => 'NEW-CODE',
        'name' => 'Renamed Machine',
        'status' => MachineStatus::ACTIVE->value,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('admin cannot update machine to use duplicate code', function () {
    $otherMachine = Machine::factory()->create(['code' => 'TAKEN-CODE']);
    $machine = Machine::factory()->create(['code' => 'MY-CODE']);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->putJson(route('api.backoffice.v1.machine.update', $machine), [
        'code' => 'TAKEN-CODE',
        'name' => 'Thief',
        'status' => MachineStatus::ACTIVE->value,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('admin can delete a machine', function () {
    $machine = Machine::factory()->create();

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->deleteJson(route('api.backoffice.v1.machine.destroy', $machine));

    $response->assertStatus(200);

    $this->assertSoftDeleted('machines', ['id' => $machine->id]);
});

test('admin can search machines by name or code', function () {
    Machine::factory()->create(['name' => 'Alpha Machine', 'code' => 'MCH-A']);
    Machine::factory()->create(['name' => 'Hidden Machine', 'code' => 'MCH-B']);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->getJson(route('api.backoffice.v1.machine.index', ['search' => 'Alpha']));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Alpha Machine'])
        ->assertJsonMissing(['name' => 'Hidden Machine']);

    $response = $this->getJson(route('api.backoffice.v1.machine.index', ['search' => 'MCH-A']));
    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['code' => 'MCH-A']);
});

test('admin can filter machines by status', function () {
    Machine::factory()->create(['status' => MachineStatus::ACTIVE->value, 'name' => 'Active Machine']);
    Machine::factory()->create(['status' => MachineStatus::MAINTENANCE->value, 'name' => 'Maint Machine']);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->getJson(route('api.backoffice.v1.machine.index', ['status' => MachineStatus::ACTIVE->value]));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Active Machine'])
        ->assertJsonMissing(['name' => 'Maint Machine']);
});
