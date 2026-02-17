<?php

use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->machine = Machine::factory()->create(['status' => 'active']);

    Sanctum::actingAs(
        $this->user,
        [\App\Enums\SystemAbility::MACHINE->value]
    );
});

describe('index', function () {
    test('mengembalikan daftar log entry dengan format paginasi yang valid', function () {
        MachineLog::create([
            'user_id' => $this->user->id,
            'machine_code' => $this->machine->code,
            'event' => 'login_success',
            'log_message' => 'User logged in',
        ]);

        $response = $this->getJson(route('api.machine.v1.log-entry.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'machine_code', 'user_id', 'event', 'log_message', 'created_at'],
                ],
                'meta',
            ])
            ->assertJsonFragment(['success' => true]);
    });

    test('mengembalikan daftar log entry berdasarkan machine_code', function () {
        $otherMachine = Machine::factory()->create(['code' => 'OTHER-001']);
        MachineLog::create(['user_id' => $this->user->id, 'machine_code' => $this->machine->code, 'event' => 'A', 'log_message' => 'A']);
        MachineLog::create(['user_id' => $this->user->id, 'machine_code' => $otherMachine->code, 'event' => 'B', 'log_message' => 'B']);

        $response = $this->getJson(route('api.machine.v1.log-entry.index', ['machine_code' => $this->machine->code]));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['machine_code' => $this->machine->code]);
    });

    test('mengembalikan daftar log entry berdasarkan search keyword', function () {
        MachineLog::create(['user_id' => $this->user->id, 'machine_code' => $this->machine->code, 'event' => 'machine_error_critical', 'log_message' => 'System Panic']);
        MachineLog::create(['user_id' => $this->user->id, 'machine_code' => $this->machine->code, 'event' => 'login_success', 'log_message' => 'Just Login']);

        // Search by event
        $response1 = $this->getJson(route('api.machine.v1.log-entry.index', ['search' => 'critical']));
        $response1->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['event' => 'machine_error_critical']);

        // Search by message
        $response2 = $this->getJson(route('api.machine.v1.log-entry.index', ['search' => 'Panic']));
        $response2->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['log_message' => 'System Panic']);
    });
});

describe('store', function () {
    test('berhasil membuat log entry baru (standard event)', function () {
        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $this->machine->code,
            'event' => 'login_success',
            'log_message' => 'Login successful',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Log entry created successfully.',
            ]);

        $this->assertDatabaseHas('machine_logs', [
            'machine_code' => $this->machine->code,
            'event' => 'login_success',
            'log_message' => 'Login successful',
        ]);
    });

    test('berhasil membuat log entry baru (custom event - flexible string)', function () {
        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $this->machine->code,
            'event' => 'machine_overheat_warning', // Custom string
            'log_message' => 'Temperature reached 90C',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('machine_logs', [
            'event' => 'machine_overheat_warning',
            'log_message' => 'Temperature reached 90C',
        ]);
    });

    test('mengembalikan error validasi 422 ketika machine code tidak ditemukan', function () {
        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => 'INVALID-CODE',
            'event' => 'error',
            'log_message' => 'Something wrong',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['machine_code']);
    });

    test('mengembalikan error validasi 422 ketika machine status inactive', function () {
        $inactiveMachine = Machine::factory()->create(['status' => 'inactive']);

        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $inactiveMachine->code,
            'event' => 'error',
            'log_message' => 'Something wrong',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['machine_code']);
    });

    test('mengembalikan error validasi 422 ketika event kosong', function () {
        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $this->machine->code,
            'event' => '',
            'log_message' => 'Message',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event']);
    });
});
