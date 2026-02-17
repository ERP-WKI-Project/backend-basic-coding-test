<?php

use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('user machine activity report → mengembalikan data activity dalam rentang tanggal', function () {
    $user = User::factory()->create();
    $machine = Machine::factory()->create();

    // Create logs within range
    $log1 = new MachineLog([
        'user_id' => $user->id,
        'machine_code' => $machine->code,
        'event' => 'login_success',
        'log_message' => 'User logged in',
        'created_at' => now()->subDay(),
    ]);
    $log1->setCreatedAt(now()->subDay());
    $log1->save();

    // Create logs outside range (too old)
    $log2 = new MachineLog([
        'user_id' => $user->id,
        'machine_code' => $machine->code,
        'event' => 'old_log',
        'log_message' => 'Old message',
    ]);
    $log2->setCreatedAt(now()->subDays(10));
    $log2->save();

    Sanctum::actingAs(
        User::factory()->create(),
        [\App\Enums\SystemAbility::BACKOFFICE->value]
    );

    $response = $this->getJson('/api/backoffice/v1/report/user-machine-activity?start_date=' . now()->subDays(5)->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'log_id',
                    'user' => ['id', 'name', 'employee_number'],
                    'machine' => ['code', 'name'],
                    'event',
                    'message',
                    'created_at',
                ]
            ],
            'meta' => ['current_page', 'last_page', 'total']
        ]);

    // Should only contain the recent log
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.event'))->toBe('login_success');
});

test('user machine activity report → filter berdasarkan user_id', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $machine = Machine::factory()->create();

    MachineLog::create([
        'user_id' => $user1->id,
        'machine_code' => $machine->code,
        'event' => 'log_user_1',
        'log_message' => 'Message 1',
    ]);

    MachineLog::create([
        'user_id' => $user2->id,
        'machine_code' => $machine->code,
        'event' => 'log_user_2',
        'log_message' => 'Message 2',
    ]);

    Sanctum::actingAs(
        User::factory()->create(),
        [\App\Enums\SystemAbility::BACKOFFICE->value]
    );

    $response = $this->getJson('/api/backoffice/v1/report/user-machine-activity?start_date=' . now()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d') . '&user_id=' . $user1->id);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.event'))->toBe('log_user_1');
});

test('user machine activity report → filter berdasarkan machine_code', function () {
    $user = User::factory()->create();
    $machine1 = Machine::factory()->create();
    $machine2 = Machine::factory()->create();

    MachineLog::create([
        'user_id' => $user->id,
        'machine_code' => $machine1->code,
        'event' => 'log_machine_1',
        'log_message' => 'Message 1',
    ]);

    MachineLog::create([
        'user_id' => $user->id,
        'machine_code' => $machine2->code,
        'event' => 'log_machine_2',
        'log_message' => 'Message 2',
    ]);

    Sanctum::actingAs(
        User::factory()->create(),
        [\App\Enums\SystemAbility::BACKOFFICE->value]
    );

    $response = $this->getJson('/api/backoffice/v1/report/user-machine-activity?start_date=' . now()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d') . '&machine_code=' . $machine1->code);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.machine.code'))->toBe($machine1->code);
});

test('user machine activity report → validasi range tanggal (start <= end)', function () {
    Sanctum::actingAs(
        User::factory()->create(),
        [\App\Enums\SystemAbility::BACKOFFICE->value]
    );

    $response = $this->getJson('/api/backoffice/v1/report/user-machine-activity?start_date=' . now()->format('Y-m-d') . '&end_date=' . now()->subDay()->format('Y-m-d'));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['start_date', 'end_date']);
});

test('user machine activity report → unauthenticated', function () {
    $response = $this->getJson('/api/backoffice/v1/report/user-machine-activity');

    $response->assertStatus(401);
});
