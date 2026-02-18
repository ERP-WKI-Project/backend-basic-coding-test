<?php

use App\Models\Machine;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    // Authenticate as backoffice user
    $user = User::where('employee_number', '000001')->first();
    Sanctum::actingAs($user, [\App\Enums\SystemAbility::BACKOFFICE->value]);
});

describe('BackOffice Machine Management', function () {

    test('index returns paginated list of machines', function () {
        $response = $this->getJson('/api/backoffice/v1/machine');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'machine_code',
                        'name',
                        'description',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    });

    test('index with search query filters machines', function () {
        $response = $this->getJson('/api/backoffice/v1/machine?q=FILLING');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'machine_code',
                        'name',
                        'description',
                    ]
                ]
            ]);
    });

    test('index with limit parameter returns correct number of items', function () {
        $response = $this->getJson('/api/backoffice/v1/machine?limit=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['per_page']
            ]);

        expect($response->json('meta.per_page'))->toBe(5);
    });

    test('store creates new machine with valid data', function () {
        $machineData = [
            'name' => 'New Machine',
            'description' => 'Test machine description',
        ];

        $response = $this->postJson('/api/backoffice/v1/machine', $machineData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'machine_code',
                    'name',
                    'description',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment([
                'name' => 'New Machine',
                'description' => 'Test machine description',
            ]);

        $this->assertDatabaseHas('machines', [
            'name' => 'New Machine',
            'description' => 'Test machine description',
        ]);
    });

    test('store with missing required fields returns validation error', function () {
        $response = $this->postJson('/api/backoffice/v1/machine', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    test('show returns machine details by machine code', function () {
        $machine = Machine::first();

        $response = $this->getJson("/api/backoffice/v1/machine/{$machine->machine_code}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'machine_code',
                    'name',
                    'description',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment([
                'machine_code' => $machine->machine_code,
            ]);
    });

    test('show with non-existent machine code returns 404', function () {
        $response = $this->getJson('/api/backoffice/v1/machine/NON-EXISTENT-CODE');

        $response->assertStatus(404);
    });

    test('update modifies machine with valid data', function () {
        $machine = Machine::first();

        $response = $this->putJson("/api/backoffice/v1/machine/{$machine->machine_code}", [
            'name' => 'Updated Machine Name',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Machine Name',
                'description' => 'Updated description',
            ]);

        $this->assertDatabaseHas('machines', [
            'machine_code' => $machine->machine_code,
            'name' => 'Updated Machine Name',
        ]);
    });

    test('update with missing required fields returns validation error', function () {
        $machine = Machine::first();

        $response = $this->putJson("/api/backoffice/v1/machine/{$machine->machine_code}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    test('destroy soft deletes machine', function () {
        $machine = Machine::first();

        $response = $this->deleteJson("/api/backoffice/v1/machine/{$machine->machine_code}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Machine deleted successfully'
            ]);

        $this->assertSoftDeleted('machines', [
            'machine_code' => $machine->machine_code,
        ]);
    });

    test('destroy with non-existent machine code returns 404', function () {
        $response = $this->deleteJson('/api/backoffice/v1/machine/NON-EXISTENT-CODE');

        $response->assertStatus(404);
    });

    test('unauthenticated request to machine endpoints returns 401', function () {
        // Reset authentication by creating a new test instance without Sanctum
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/backoffice/v1/machine');

        $response->assertStatus(401);
    });

    test('request with wrong ability returns 403', function () {
        $user = User::where('employee_number', '000001')->first();
        Sanctum::actingAs($user, [\App\Enums\SystemAbility::MACHINE->value]);

        $response = $this->getJson('/api/backoffice/v1/machine');

        $response->assertStatus(403);
    });
});
