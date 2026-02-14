<?php


use App\Models\User;
use App\Models\Machine;
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

test('admin can list machines', function () {
    Machine::factory()->count(3)->create();

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->getJson(route('api.backoffice.v1.machine.index'));

    // MachineResource::collection() wraps data in 'data' and puts pagination links/meta in 'links' and 'meta'
    $response->assertStatus(200)
             ->assertJsonStructure(['data', 'links', 'meta'])
             ->assertJsonCount(3, 'data');
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

test('admin can delete a machine', function () {
    $machine = Machine::factory()->create();

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->deleteJson(route('api.backoffice.v1.machine.destroy', $machine));

    $response->assertStatus(204);

    $this->assertSoftDeleted('machines', ['id' => $machine->id]);
});

test('admin can search machines by name or code', function () {
    Machine::factory()->create(['name' => 'Alpha Machine', 'code' => 'MCH-A']);
    Machine::factory()->create(['name' => 'Beta Machine', 'code' => 'MCH-B']);
    Machine::factory()->create(['name' => 'Gamma Machine', 'code' => 'MCH-C']);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    // Search by name
    $responseName = $this->getJson(route('api.backoffice.v1.machine.index', ['search' => 'Alpha']));
    $responseName->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['name' => 'Alpha Machine']);

    // Search by code
    $responseCode = $this->getJson(route('api.backoffice.v1.machine.index', ['search' => 'MCH-B']));
    $responseCode->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['code' => 'MCH-B']);
});

test('admin can filter machines by status', function () {
    Machine::factory()->create(['status' => 'active']);
    Machine::factory()->create(['status' => 'maintenance']);
    Machine::factory()->create(['status' => 'inactive']);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    // Filter by active
    $response = $this->getJson(route('api.backoffice.v1.machine.index', ['status' => 'active']));
    $response->assertStatus(200)
             ->assertJsonCount(1, 'data')
             ->assertJsonFragment(['status' => 'active']);

    // Filter by maintenance
    $response = $this->getJson(route('api.backoffice.v1.machine.index', ['status' => 'maintenance']));
    $response->assertStatus(200)
             ->assertJsonCount(1, 'data')
             ->assertJsonFragment(['status' => 'maintenance']);
});
