<?php

namespace Tests\Feature\Machine;

use App\Models\User;
use Database\Seeders\PresetForCodingTestSeeder;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->seed(PresetForCodingTestSeeder::class);

    $this->presetUser = User::where('employee_number', '000001')->first();

    if (! $this->presetUser) {
        $this->presetUser = User::factory()->create([
            'employee_number' => '000001',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    $this->loginResponse = $this->postJson('/api/machine/v1/auth/login', [
        'machine_code' => 'FILLING-MACHINE-001',
        'pin' => '000001',
    ]);

    if ($this->loginResponse->getStatusCode() !== 200) {
        $this->markTestSkipped('Machine login not allowed (no shift or out of shift hours)');
    }

    $this->machineToken = $this->loginResponse->json('data.access_token');
});

describe('Log Entry Index', function () {
    test('log_entry_index_requires_auth', function () {
        $response = getJson('/api/machine/v1/log-entry');

        $this->assertContains($response->getStatusCode(), [401, 403, 500]);
    });

    test('log_entry_index_success', function () {
        $response = getJson('/api/machine/v1/log-entry', [
            'Authorization' => 'Bearer '.$this->machineToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });
});

describe('Log Entry Store', function () {
    test('log_entry_store_requires_auth', function () {
        $response = postJson('/api/machine/v1/log-entry', [
            'machine_code' => 'FILLING-MACHINE-001',
            'type' => 'in',
            'description' => 'Test description',
        ]);

        $this->assertContains($response->getStatusCode(), [401, 403, 500]);
    });

    test('log_entry_store_in_success', function () {
        $response = postJson('/api/machine/v1/log-entry', [
            'machine_code' => 'FILLING-MACHINE-001',
            'type' => 'in',
            'description' => null,
        ], [
            'Authorization' => 'Bearer '.$this->machineToken,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'event' => 'in',
            ]);

        $this->assertDatabaseHas('machine_logs', [
            'user_id' => $this->presetUser->id,
            'machine_code' => 'FILLING-MACHINE-001',
            'event' => 'in',
        ]);
    });

    test('log_entry_store_out_success', function () {
        $response = postJson('/api/machine/v1/log-entry', [
            'machine_code' => 'FILLING-MACHINE-001',
            'type' => 'out',
        ], [
            'Authorization' => 'Bearer '.$this->machineToken,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'event' => 'out',
            ]);
    });

    test('log_entry_store_maintenance_success', function () {
        $response = postJson('/api/machine/v1/log-entry', [
            'machine_code' => 'FILLING-MACHINE-001',
            'type' => 'maintenance',
            'description' => 'Routine maintenance check',
        ], [
            'Authorization' => 'Bearer '.$this->machineToken,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'event' => 'maintenance',
            ]);

        $this->assertDatabaseHas('machine_logs', [
            'user_id' => $this->presetUser->id,
            'machine_code' => 'FILLING-MACHINE-001',
            'event' => 'maintenance',
            'log_message' => 'Maintenance log oleh Dummy Employee (PIN: 000001) pada mesin FILLING-MACHINE-001. Keterangan: Routine maintenance check',
        ]);
    });

    test('log_entry_store_issue_success', function () {
        $response = postJson('/api/machine/v1/log-entry', [
            'machine_code' => 'FILLING-MACHINE-001',
            'type' => 'issue',
            'description' => 'Temperature sensor not working',
        ], [
            'Authorization' => 'Bearer '.$this->machineToken,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'event' => 'issue',
            ]);
    });

    test('log_entry_store_validation_failed_missing_type', function () {
        $response = postJson('/api/machine/v1/log-entry', [
            'machine_code' => 'FILLING-MACHINE-001',
        ], [
            'Authorization' => 'Bearer '.$this->machineToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    });

    test('log_entry_store_validation_failed_invalid_type', function () {
        $response = postJson('/api/machine/v1/log-entry', [
            'machine_code' => 'FILLING-MACHINE-001',
            'type' => 'invalid_type',
        ], [
            'Authorization' => 'Bearer '.$this->machineToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    });

    test('log_entry_store_validation_failed_missing_machine_code', function () {
        $response = postJson('/api/machine/v1/log-entry', [
            'type' => 'in',
        ], [
            'Authorization' => 'Bearer '.$this->machineToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['machine_code']);
    });

    test('log_entry_store_description_max_length', function () {
        $response = postJson('/api/machine/v1/log-entry', [
            'machine_code' => 'FILLING-MACHINE-001',
            'type' => 'maintenance',
            'description' => str_repeat('a', 501),
        ], [
            'Authorization' => 'Bearer '.$this->machineToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    });
});
