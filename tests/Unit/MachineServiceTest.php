<?php

use App\DTOs\MachineDto;
use App\Models\Machine;
use App\Models\User;
use App\Services\MachineService;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->service = new MachineService();
});

describe('MachineService - Create', function () {
    test('can create machine with valid data', function () {
        $dto = new MachineDto(
            machineCode: 'TEST-001',
            name: 'Test Machine',
            description: 'Test description',
        );

        $machine = $this->service->createMachine($dto);

        expect($machine)->toBeInstanceOf(Machine::class)
            ->and($machine->machine_code)->toBe('TEST-001')
            ->and($machine->name)->toBe('Test Machine')
            ->and($machine->is_active)->toBeTrue();
    });

    test('creates machine with minimal data', function () {
        $dto = new MachineDto(
            machineCode: 'MIN-001',
            name: 'Minimal Machine',
        );

        $machine = $this->service->createMachine($dto);

        expect($machine)->toBeInstanceOf(Machine::class)
            ->and($machine->machine_code)->toBe('MIN-001')
            ->and($machine->is_active)->toBeTrue(); // default value
    });

    test('creates machine within transaction', function () {
        $dto = new MachineDto(
            machineCode: 'TRANS-001',
            name: 'Transaction Test',
        );

        expect(fn() => $this->service->createMachine($dto))
            ->not->toThrow(\Exception::class);

        $this->assertDatabaseHas('machines', ['machine_code' => 'TRANS-001']);
    });
});

describe('MachineService - Update', function () {
    test('can update machine fields', function (array $updateData) {
        $machine = Machine::factory()->create([
            'machine_code' => 'ORIGINAL-001',
            'name' => 'Original Name',
            'is_active' => true,
        ]);

        $dto = MachineDto::fromRequest(array_merge([
            'machine_code' => $machine->machine_code,
            'name' => $machine->name,
            'description' => $machine->description,
        ], $updateData));

        $updated = $this->service->updateMachine($machine, $dto);

        expect($updated)->toBeInstanceOf(Machine::class);
        
        foreach ($updateData as $key => $value) {
            expect($updated->$key)->toBe($value);
        }
    })->with([
        'update name' => [['name' => 'Updated Name']],
        'update machine_code' => [['machine_code' => 'UPDATED-001']],
        'update description' => [['description' => 'New description']],
        'update multiple fields' => [[
            'name' => 'Multi Update',
            'description' => 'Multi description',
        ]],
    ]);

    test('returns fresh model instance', function () {
        $machine = Machine::factory()->create();
        $originalUpdatedAt = $machine->updated_at;

        sleep(1);
        
        $dto = new MachineDto(
            machineCode: $machine->machine_code,
            name: 'Fresh Test',
            description: $machine->description,
        );
        
        $updated = $this->service->updateMachine($machine, $dto);

        expect($updated->updated_at->timestamp)
            ->toBeGreaterThan($originalUpdatedAt->timestamp);
    });
});

describe('MachineService - Delete', function () {
    test('can delete machine without active shifts', function () {
        $machine = Machine::factory()->create();

        $result = $this->service->deleteMachine($machine);

        expect($result)->toBeTrue();
        $this->assertSoftDeleted('machines', ['id' => $machine->id]);
    });

    test('cannot delete machine with active shifts', function () {
        $machine = Machine::factory()->create(['machine_code' => 'BUSY-001']);
        
        // Create active shift using existing user from seeder
        $user = User::where('employee_number', '000001')->first();
        $shift = \App\Models\Shift::first();
        \App\Models\UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'shift_date' => now()->addDay()->format('Y-m-d'),
            'machine_code' => 'BUSY-001',
        ]);

        expect(fn() => $this->service->deleteMachine($machine))
            ->toThrow(\Exception::class, 'Tidak dapat menghapus mesin yang masih memiliki shift aktif.');

        $this->assertDatabaseHas('machines', ['id' => $machine->id, 'deleted_at' => null]);
    });

    test('can delete machine with past shifts', function () {
        $machine = Machine::factory()->create(['machine_code' => 'PAST-001']);
        
        // Create past shift (yesterday) using existing user from seeder
        $user = User::where('employee_number', '000001')->first();
        $shift = \App\Models\Shift::first();
        \App\Models\UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'shift_date' => now()->subDay()->format('Y-m-d'),
            'machine_code' => 'PAST-001',
        ]);

        $result = $this->service->deleteMachine($machine);

        expect($result)->toBeTrue();
        $this->assertSoftDeleted('machines', ['id' => $machine->id]);
    });

    test('delete operation uses transaction', function () {
        $machine = Machine::factory()->create();

        expect(fn() => $this->service->deleteMachine($machine))
            ->not->toThrow(\Exception::class);
    });
});

describe('MachineService - Restore', function () {
    test('can restore soft-deleted machine', function () {
        $machine = Machine::factory()->create();
        $machine->delete();

        $restored = $this->service->restoreMachine($machine->id);

        expect($restored)->toBeInstanceOf(Machine::class)
            ->and($restored->deleted_at)->toBeNull();

        $this->assertDatabaseHas('machines', [
            'id' => $machine->id,
            'deleted_at' => null,
        ]);
    });

    test('throws exception for non-existent machine', function () {
        expect(fn() => $this->service->restoreMachine(99999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });
});
