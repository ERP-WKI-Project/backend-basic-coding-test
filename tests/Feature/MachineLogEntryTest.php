<?php

namespace Tests\Feature;

use App\Enums\MachineLog\MachineLogEventEnum;
use App\Enums\MachineLog\SeverityEnum;
use App\Enums\SystemAbility;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MachineLogEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $worker;
    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->worker = User::factory()->create();
        $this->machine = Machine::factory()->create();
    }

    // ========== Authentication Tests ==========

    public function test_unauthenticated_user_cannot_access_log_entries(): void
    {
        $response = $this->getJson(route('api.machine.v1.log-entry.index'));
        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_create_log_entry(): void
    {
        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN->value,
            'log_message' => 'Test log',
        ]);
        $response->assertStatus(401);
    }

    // ========== Index/List Tests ==========

    public function test_can_get_own_log_entries(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        // Create some logs for this user
        MachineLog::create([
            'user_id' => $this->worker->id,
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN,
            'log_message' => 'Clocked in',
        ]);

        MachineLog::create([
            'user_id' => $this->worker->id,
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::CLOCK_OUT,
            'log_message' => 'Clocked out',
        ]);

        // Create log for another user (should not appear)
        $otherUser = User::factory()->create();
        MachineLog::create([
            'user_id' => $otherUser->id,
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN,
            'log_message' => 'Other user log',
        ]);

        $response = $this->getJson(route('api.machine.v1.log-entry.index'));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'machine_code',
                        'event',
                        'log_message',
                        'severity',
                        'metadata',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_can_filter_logs_by_machine_code(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        $machine2 = Machine::factory()->create();

        // Create logs on different machines
        MachineLog::create([
            'user_id' => $this->worker->id,
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN,
            'log_message' => 'Machine 1 log',
        ]);

        MachineLog::create([
            'user_id' => $this->worker->id,
            'machine_code' => $machine2->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN,
            'log_message' => 'Machine 2 log',
        ]);

        $response = $this->getJson(route('api.machine.v1.log-entry.index', [
            'machine_code' => $this->machine->machine_code,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.machine_code', $this->machine->machine_code);
    }

    public function test_logs_are_ordered_by_newest_first(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        // Create logs with explicit created_at using DB::table to bypass fillable
        DB::table('machine_logs')->insert([
            'ulid' => (string) \Illuminate\Support\Str::ulid(),
            'user_id' => $this->worker->id,
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN->value,
            'log_message' => 'First log',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        DB::table('machine_logs')->insert([
            'ulid' => (string) \Illuminate\Support\Str::ulid(),
            'user_id' => $this->worker->id,
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::CLOCK_OUT->value,
            'log_message' => 'Second log',
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        DB::table('machine_logs')->insert([
            'ulid' => (string) \Illuminate\Support\Str::ulid(),
            'user_id' => $this->worker->id,
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN->value,
            'log_message' => 'Third log',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson(route('api.machine.v1.log-entry.index'));

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Verify order: newest first
        $this->assertEquals('Third log', $data[0]['log_message']);
        $this->assertEquals('Second log', $data[1]['log_message']);
        $this->assertEquals('First log', $data[2]['log_message']);
    }

    // ========== Store/Create Tests ==========

    public function test_can_create_basic_log_entry(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN->value,
            'log_message' => 'Manual clock in',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Log entry created successfully')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'machine_code',
                    'event',
                    'log_message',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('machine_logs', [
            'user_id' => $this->worker->id,
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN->value,
            'log_message' => 'Manual clock in',
        ]);
    }

    public function test_can_create_log_entry_with_severity(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::MACHINE_FAILURE->value,
            'log_message' => 'Machine stopped working',
            'severity' => SeverityEnum::HIGH->value,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('machine_logs', [
            'user_id' => $this->worker->id,
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::MACHINE_FAILURE->value,
            'severity' => SeverityEnum::HIGH->value,
        ]);
    }

    public function test_can_create_log_entry_with_metadata(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        $metadata = [
            'error_code' => 'E404',
            'sensor_reading' => 75.5,
            'parts_produced' => 150,
        ];

        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::MACHINE_FAILURE->value,
            'log_message' => 'Sensor malfunction',
            'severity' => SeverityEnum::MEDIUM->value,
            'metadata' => $metadata,
        ]);

        $response->assertStatus(201);

        $log = MachineLog::where('user_id', $this->worker->id)
            ->where('log_message', 'Sensor malfunction')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($metadata, $log->metadata);
    }

    public function test_can_create_all_event_types(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        $events = [
            MachineLogEventEnum::CLOCK_IN,
            MachineLogEventEnum::CLOCK_OUT,
            MachineLogEventEnum::CLOCK_OUT_EARLY,
            MachineLogEventEnum::MACHINE_FAILURE,
            MachineLogEventEnum::MACHINE_TRANSFER,
            MachineLogEventEnum::BREAK_START,
            MachineLogEventEnum::BREAK_END,
        ];

        foreach ($events as $event) {
            $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
                'machine_code' => $this->machine->machine_code,
                'event' => $event->value,
                'log_message' => "Testing {$event->value}",
            ]);

            $response->assertStatus(201);
        }

        $this->assertDatabaseCount('machine_logs', count($events));
    }

    // ========== Validation Tests ==========

    public function test_machine_code_is_required(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'event' => MachineLogEventEnum::CLOCK_IN->value,
            'log_message' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['machine_code']);
    }

    public function test_machine_code_must_exist(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => 'NONEXISTENT-CODE',
            'event' => MachineLogEventEnum::CLOCK_IN->value,
            'log_message' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['machine_code']);
    }

    public function test_event_is_required(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $this->machine->machine_code,
            'log_message' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event']);
    }

    public function test_event_must_be_valid_enum(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $this->machine->machine_code,
            'event' => 'INVALID_EVENT',
            'log_message' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event']);
    }

    public function test_log_message_is_required(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN->value,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['log_message']);
    }

    public function test_severity_must_be_valid_enum_if_provided(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::MACHINE_FAILURE->value,
            'log_message' => 'Test',
            'severity' => 'INVALID_SEVERITY',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['severity']);
    }

    public function test_metadata_must_be_array_if_provided(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::MACHINE_FAILURE->value,
            'log_message' => 'Test',
            'metadata' => 'not-an-array',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['metadata']);
    }

    // ========== Integration Tests ==========

    public function test_log_entry_integrates_with_shift_system(): void
    {
        Sanctum::actingAs($this->worker, [SystemAbility::MACHINE->value]);

        // Create a shift assignment
        $shift = Shift::factory()->create([
            'day_of_week' => Carbon::now()->dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $shift->id,
            'shift_date' => Carbon::now()->format('Y-m-d'),
            'machine_code' => $this->machine->machine_code,
        ]);

        // Worker creates a log entry
        $response = $this->postJson(route('api.machine.v1.log-entry.store'), [
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::MACHINE_FAILURE->value,
            'log_message' => 'Machine overheating',
            'severity' => SeverityEnum::HIGH->value,
            'metadata' => [
                'temperature' => 95,
                'shift_id' => $shift->id,
                'user_shift_id' => $userShift->id,
            ],
        ]);

        $response->assertStatus(201);

        // Verify log can be retrieved
        $response = $this->getJson(route('api.machine.v1.log-entry.index'));
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
