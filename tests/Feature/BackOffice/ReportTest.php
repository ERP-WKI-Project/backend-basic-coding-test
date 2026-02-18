<?php

use App\Models\User;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\UserShift;
use App\Models\Shift;
use App\Enums\SystemAbility;
use App\Enums\MachineLog\EventEnum;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    // Create necessary data for filtering tests
    $this->machine = Machine::factory()->create(['code' => 'MCH-001', 'name' => 'Drill Press']);
    $this->targetUser = User::factory()->create(['name' => 'Operator One']);

    // Create a shift 
    $this->shift = Shift::factory()->create(['name' => 'Morning Shift']);

    $this->userShift = UserShift::factory()->create([
        'user_id' => $this->targetUser->id,
        'shift_id' => $this->shift->id,
        'machine_id' => $this->machine->id,
        'machine_code' => $this->machine->code,
        'shift_date' => now()->toDateString(),
    ]);
});

test('unauthenticated user cannot access report', function () {
    $this->getJson(route('api.backoffice.v1.report.user-machine-activity'))
        ->assertStatus(401);
});

test('user without backoffice access cannot access report', function () {
    Sanctum::actingAs(User::factory()->create(), [SystemAbility::MACHINE->value]);

    $this->getJson(route('api.backoffice.v1.report.user-machine-activity'))
        ->assertStatus(403);
});

test('admin can generate user machine activity report', function () {
    Sanctum::actingAs($this->user, [SystemAbility::BACKOFFICE->value]);

    MachineLog::factory()->count(5)->create();

    $this->getJson(route('api.backoffice.v1.report.user-machine-activity'))
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonCount(5, 'data');
});

test('admin can filter report by date range', function () {
    Sanctum::actingAs($this->user, [SystemAbility::BACKOFFICE->value]);

    $date1 = Carbon::now()->subDays(5);
    $date2 = Carbon::now()->subDays(2);

    // Log inside range
    MachineLog::factory()->create(['created_at' => $date1->copy()->addDay()]);

    // Log outside range (before)
    MachineLog::factory()->create(['created_at' => $date1->copy()->subDay()]);

    // Log outside range (after)
    MachineLog::factory()->create(['created_at' => $date2->copy()->addDay()]);

    $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
        'start_date' => $date1->toDateString(),
        'end_date' => $date2->toDateString(),
    ]));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('admin can filter report by user', function () {
    Sanctum::actingAs($this->user, [SystemAbility::BACKOFFICE->value]);

    // Log for target user
    $log1 = MachineLog::factory()->create(['user_id' => $this->targetUser->id]);

    // Log for another user
    MachineLog::factory()->create(['user_id' => User::factory()->create()->id]);

    \Illuminate\Support\Facades\Log::info("Target User ID: " . $this->targetUser->id);
    \Illuminate\Support\Facades\Log::info("Target User ULID: " . $this->targetUser->ulid);
    \Illuminate\Support\Facades\Log::info("Log 1 User ID: " . $log1->user_id);
    \Illuminate\Support\Facades\Log::info("Log 1 User ULID: " . $log1->user->ulid);

    $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
        'user_id' => $this->targetUser->ulid,
    ]));

    \Illuminate\Support\Facades\Log::info("Response Count: " . count($response->json('data')));
    \Illuminate\Support\Facades\Log::info("Response User IDs: ", collect($response->json('data'))->pluck('user.id')->toArray());
    \Illuminate\Support\Facades\Log::info("Response Messages: ", collect($response->json('data'))->pluck('log_message')->toArray());

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('admin can filter report by machine', function () {
    Sanctum::actingAs($this->user, [SystemAbility::BACKOFFICE->value]);

    // Log for target machine
    MachineLog::factory()->create(['machine_id' => $this->machine->id]);

    // Log for another machine
    MachineLog::factory()->create(['machine_id' => Machine::factory()->create()->id]);

    $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
        'machine_id' => $this->machine->ulid,
    ]));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('validation failures', function () {
    Sanctum::actingAs($this->user, [SystemAbility::BACKOFFICE->value]);

    $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
        'start_date' => 'invalid-date',
        'user_id' => 'not-a-ulid',
    ]))->assertStatus(422)
        ->assertJsonValidationErrors(['start_date', 'user_id']);

    // End date before start date
    $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
        'start_date' => '2024-01-10',
        'end_date' => '2024-01-01',
    ]))->assertStatus(422)
        ->assertJsonValidationErrors(['end_date']);
});
