<?php

use App\Models\Machine;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    
    // Authenticate as backoffice user
    Sanctum::actingAs(
        User::where('employee_number', '000001')->first(),
        [\App\Enums\SystemAbility::BACKOFFICE->value]
    );
});

describe('Machine Management - List', function () {
    test('can list all machines', function () {
        Machine::factory()->count(3)->create();

        $response = $this->getJson('/api/backoffice/v1/machine');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'machine_code',
                        'name',
                        'description',
                        'is_active',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                    ]
                ]
            ]);
    });

    test('requires authentication', function () {
        Sanctum::actingAs(
            User::where('employee_number', '000001')->first(),
            ['WRONG_ABILITY']
        );

        $response = $this->getJson('/api/backoffice/v1/machine');
        $response->assertStatus(403);
    });
});

describe('Machine Management - Create', function () {
    test('can create machine with valid data', function (array $data) {
        $response = $this->postJson('/api/backoffice/v1/machine', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'machine_code',
                    'name',
                    'description',
                    'is_active',
                ]
            ]);

        $this->assertDatabaseHas('machines', [
            'machine_code' => $data['machine_code'],
            'name' => $data['name'],
        ]);
    })->with([
        'complete data' => [[
            'machine_code' => 'TEST-MACHINE-001',
            'name' => 'Test Machine 1',
            'description' => 'This is a test machine',
            'is_active' => true,
        ]],
        'minimal data' => [[
            'machine_code' => 'TEST-MACHINE-002',
            'name' => 'Test Machine 2',
        ]],
        'with inactive status' => [[
            'machine_code' => 'TEST-MACHINE-003',
            'name' => 'Test Machine 3',
            'is_active' => false,
        ]],
    ]);

    test('validates required fields', function (array $data, string $errorField) {
        $response = $this->postJson('/api/backoffice/v1/machine', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors($errorField);
    })->with([
        'missing machine_code' => [
            ['name' => 'Test Machine'],
            'machine_code'
        ],
        'missing name' => [
            ['machine_code' => 'TEST-001'],
            'name'
        ],
        'duplicate machine_code' => [
            function () {
                Machine::factory()->create(['machine_code' => 'DUPLICATE-001']);
                return ['machine_code' => 'DUPLICATE-001', 'name' => 'Test'];
            },
            'machine_code'
        ],
        'invalid machine_code format' => [
            ['machine_code' => 'invalid code', 'name' => 'Test'],
            'machine_code'
        ],
        'machine_code too long' => [
            ['machine_code' => str_repeat('A', 51), 'name' => 'Test'],
            'machine_code'
        ],
    ]);
});

describe('Machine Management - Show', function () {
    test('can show machine details', function () {
        $machine = Machine::factory()->create();

        $response = $this->getJson("/api/backoffice/v1/machine/{$machine->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $machine->id,
                    'machine_code' => $machine->machine_code,
                    'name' => $machine->name,
                ]
            ]);
    });

    test('returns 404 for non-existent machine', function () {
        $response = $this->getJson('/api/backoffice/v1/machine/99999');
        $response->assertStatus(404);
    });
});

describe('Machine Management - Update', function () {
    test('can update machine', function (array $updateData) {
        $machine = Machine::factory()->create([
            'machine_code' => 'ORIGINAL-001',
            'name' => 'Original Name',
        ]);

        $response = $this->putJson("/api/backoffice/v1/machine/{$machine->id}", $updateData);

        $response->assertStatus(200);
        
        $machine->refresh();
        foreach ($updateData as $key => $value) {
            expect($machine->$key)->toBe($value);
        }
    })->with([
        'update name only' => [['name' => 'Updated Name']],
        'update machine_code' => [['machine_code' => 'UPDATED-001']],
        'update description' => [['description' => 'Updated description']],
        'update all fields' => [[
            'machine_code' => 'FULL-UPDATE-001',
            'name' => 'Fully Updated',
            'description' => 'New description',
        ]],
    ]);

    test('validates update data', function (callable $setup, array $updateData, string $errorField) {
        $setup();
        $machine = Machine::factory()->create(['machine_code' => 'TEST-001']);

        $response = $this->putJson("/api/backoffice/v1/machine/{$machine->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors($errorField);
    })->with([
        'duplicate machine_code' => [
            function () {
                Machine::factory()->create(['machine_code' => 'EXISTING-001']);
            },
            ['machine_code' => 'EXISTING-001'],
            'machine_code'
        ],
        'invalid machine_code format' => [
            function () {},
            ['machine_code' => 'invalid code'],
            'machine_code'
        ],
    ]);
});

describe('Machine Management - Delete', function () {
    test('can delete machine without active shifts', function () {
        $machine = Machine::factory()->create();

        $response = $this->deleteJson("/api/backoffice/v1/machine/{$machine->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Mesin berhasil dihapus.']);

        $this->assertSoftDeleted('machines', ['id' => $machine->id]);
    });

    test('cannot delete machine with active shifts', function () {
        $machine = Machine::factory()->create(['machine_code' => 'ACTIVE-001']);
        
        // Create active shift for this machine using existing user from seeder
        $user = User::where('employee_number', '000001')->first();
        $shift = \App\Models\Shift::first();
        \App\Models\UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'shift_date' => now()->addDay()->format('Y-m-d'),
            'machine_code' => 'ACTIVE-001',
        ]);

        $response = $this->deleteJson("/api/backoffice/v1/machine/{$machine->id}");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Tidak dapat menghapus mesin yang masih memiliki shift aktif.']);

        $this->assertDatabaseHas('machines', ['id' => $machine->id, 'deleted_at' => null]);
    });
});
