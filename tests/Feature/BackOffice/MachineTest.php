<?php

use App\Models\Machine;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
    $this->admin = User::where('employee_number', '000001')->first();
    Sanctum::actingAs($this->admin, [\App\Enums\SystemAbility::BACKOFFICE->value]);
});

describe('Machine API', function () {
    it('can list machines', function () {
        Machine::factory()->count(3)->create();

        $response = $this->getJson('/api/backoffice/v1/machine');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'code', 'name', 'location', 'is_active', 'created_at', 'updated_at']
            ],
            'links',
            'meta',
        ]);
    });

    it('can create a machine', function () {
        $data = [
            'code' => 'TEST-MACHINE-001',
            'name' => 'Test Machine 1',
            'location' => 'Factory Floor A',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/backoffice/v1/machine', $data);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'code' => 'TEST-MACHINE-001',
            'name' => 'Test Machine 1',
        ]);
        $this->assertDatabaseHas('machines', ['code' => 'TEST-MACHINE-001']);
    });

    it('cannot create machine with duplicate code', function () {
        Machine::factory()->create(['code' => 'DUP-MACHINE-001']);

        $data = [
            'code' => 'DUP-MACHINE-001',
            'name' => 'Duplicate Machine',
        ];

        $response = $this->postJson('/api/backoffice/v1/machine', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    });

    it('can show a machine', function () {
        $machine = Machine::factory()->create();

        $response = $this->getJson("/api/backoffice/v1/machine/{$machine->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $machine->id,
            'code' => $machine->code,
        ]);
    });

    it('can update a machine', function () {
        $machine = Machine::factory()->create();

        $data = [
            'name' => 'Updated Machine Name',
            'location' => 'New Location',
        ];

        $response = $this->putJson("/api/backoffice/v1/machine/{$machine->id}", $data);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Updated Machine Name',
            'location' => 'New Location',
        ]);
    });

    it('can delete a machine', function () {
        $machine = Machine::factory()->create();

        $response = $this->deleteJson("/api/backoffice/v1/machine/{$machine->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('machines', ['id' => $machine->id]);
    });

    it('can filter by is_active', function () {
        Machine::factory()->create(['is_active' => true]);
        Machine::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/backoffice/v1/machine?is_active=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect(count($data))->toBeGreaterThan(0);
        expect($data[0]['is_active'])->toBe(true);
    });
});
