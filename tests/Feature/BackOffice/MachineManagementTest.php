<?php

use App\Enums\MachineStatus;
use App\Models\Machine;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();

    Sanctum::actingAs(
        $this->user,
        [\App\Enums\SystemAbility::BACKOFFICE->value]
    );
});

describe('index', function () {
    test('mengembalikan daftar seluruh mesin beserta format paginasi yang valid', function () {
        Machine::factory()->count(20)->create();

        $response = $this->getJson('/api/backoffice/v1/machines');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['code', 'name', 'status', 'created_at', 'updated_at'],
                ],
            ])
            ->assertJsonFragment(['success' => true]);
    });

    test('mengembalikan daftar mesin berdasarkan name', function () {
        Machine::factory()->create(['name' => 'Specific Machine']);
        Machine::factory()->create(['name' => 'Other Machine']);

        $response = $this->getJson('/api/backoffice/v1/machines?search=Specific');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Specific Machine']);
    });

    test('mengembalikan daftar mesin berdasarkan code', function () {
        Machine::factory()->create(['code' => 'MCH-123']);
        Machine::factory()->create(['code' => 'MCH-456']);

        $response = $this->getJson('/api/backoffice/v1/machines?search=MCH-123');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['code' => 'MCH-123']);
    });

    test('mengembalikan data kosong jika search tidak cocok', function () {
        Machine::factory()->count(5)->create();

        $response = $this->getJson('/api/backoffice/v1/machines?search=NONEXISTENT');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });
});

describe('store', function () {
    test('berhasil membuat mesin baru dan mengembalikan status 201', function () {
        $data = [
            'code' => 'MCH-NEW-001',
            'name' => 'New Machine',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/backoffice/v1/machines', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['success' => true, 'message' => __('messages.machine_created')])
            ->assertJsonFragment($data);

        $this->assertDatabaseHas('machines', $data);
    });

    test('berhasil membuat mesin baru tanpa status (default active)', function () {
        $data = [
            'code' => 'MCH-DEFAULT-001',
            'name' => 'Default Status Machine',
        ];

        $response = $this->postJson('/api/backoffice/v1/machines', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => MachineStatus::ACTIVE->value]);

        $this->assertDatabaseHas('machines', ['code' => 'MCH-DEFAULT-001', 'status' => MachineStatus::ACTIVE->value]);
    });

    test('mengembalikan error validasi 422 ketika request body kosong', function () {
        $response = $this->postJson('/api/backoffice/v1/machines', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name']);
    });



    test('mengembalikan error validasi 422 ketika code sudah terdaftar', function () {
        Machine::factory()->create(['code' => 'MCH-EXISTING']);

        $response = $this->postJson('/api/backoffice/v1/machines', [
            'code' => 'MCH-EXISTING',
            'name' => 'Duplicate Machine',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    });

    test('mengembalikan error validasi 422 ketika status tidak valid', function () {
        $response = $this->postJson('/api/backoffice/v1/machines', [
            'code' => 'MCH-INVALID-STATUS',
            'name' => 'Invalid Status Machine',
            'status' => 'broken',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    });
});

describe('show', function () {
    test('mengembalikan detail spesifik mesin berdasarkan code dan mengembalikan status 200', function () {
        Machine::factory()->create();
        $machine = Machine::first();

        $response = $this->getJson("/api/backoffice/v1/machines/{$machine->code}");

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true, 'code' => $machine->code]);
    });

    test('mengembalikan status 404 jika mesin tidak ditemukan', function () {
        $response = $this->getJson('/api/backoffice/v1/machines/NONEXISTENT');

        $response->assertStatus(404);
    });
});

describe('update', function () {
    test('berhasil mengupdate mesin seluruh field dan mengembalikan status 200', function () {
        $machine = Machine::factory()->create();

        $data = [
            'code' => 'MCH-UPDATED',
            'name' => 'Updated Machine Name',
            'status' => 'inactive',
        ];

        $response = $this->putJson("/api/backoffice/v1/machines/{$machine->code}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true, 'message' => __('messages.machine_updated')])
            ->assertJsonFragment($data);

        $this->assertDatabaseHas('machines', $data);
    });

    test('berhasil mengupdate mesin sebagian field tanpa mengubah field lain dan mengembalikan status 200', function () {
        $machine = Machine::factory()->create(['name' => 'Original Name', 'status' => 'active']);

        $response = $this->putJson("/api/backoffice/v1/machines/{$machine->code}", [
            'name' => 'Partial Update Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Partial Update Name', 'status' => 'active']);

        $this->assertDatabaseHas('machines', [
            'code' => $machine->code,
            'name' => 'Partial Update Name',
            'status' => 'active',
        ]);
    });

    test('berhasil mengupdate mesin dengan code milik mesin itu sendiri dan mengembalikan status 200', function () {
        $machine = Machine::factory()->create();

        $response = $this->putJson("/api/backoffice/v1/machines/{$machine->code}", [
            'code' => $machine->code,
            'name' => 'Self Update Code',
        ]);

        $response->assertStatus(200);
    });

    test('mengembalikan error validasi 422 ketika code sudah terdaftar', function () {
        $machine1 = Machine::factory()->create(['code' => 'MCH-1']);
        $machine2 = Machine::factory()->create(['code' => 'MCH-2']);

        $response = $this->putJson("/api/backoffice/v1/machines/{$machine1->code}", [
            'code' => 'MCH-2',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    });

    test('mengembalikan status 404 jika mesin tidak ditemukan', function () {
        $response = $this->putJson('/api/backoffice/v1/machines/NONEXISTENT', [
            'name' => 'Update Nonexistent',
        ]);

        $response->assertStatus(404);
    });
});

describe('destroy', function () {
    test('berhasil soft delete mesin dan mengembalikan status 200', function () {
        $machine = Machine::factory()->create();

        $response = $this->deleteJson("/api/backoffice/v1/machines/{$machine->code}");

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true, 'message' => __('messages.machine_deleted')]);

        $this->assertSoftDeleted('machines', ['id' => $machine->id]);
    });

    test('mengembalikan status 404 jika mesin tidak ditemukan', function () {
        $response = $this->deleteJson('/api/backoffice/v1/machines/NONEXISTENT');

        $response->assertStatus(404);
    });
});

describe('unauthenticated', function () {
    test('mengembalikan status 401 jika user tidak terautentikasi', function () {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/backoffice/v1/machines');
        $response = $this->postJson('/api/backoffice/v1/machines', []);
        $response = $this->putJson('/api/backoffice/v1/machines/MCH-1', []);
        $response = $this->deleteJson('/api/backoffice/v1/machines/MCH-1');

        $response->assertStatus(401);
    });
});
