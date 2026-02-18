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

class UserMachineActivityReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $worker1;
    private User $worker2;
    private Machine $machine1;
    private Machine $machine2;
    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create();
        
        $this->worker1 = User::factory()->create(['name' => 'Worker One']);
        $this->worker2 = User::factory()->create(['name' => 'Worker Two']);
        
        $this->machine1 = Machine::factory()->create();
        $this->machine2 = Machine::factory()->create();
        
        $this->shift = Shift::factory()->create([
            'name' => 'Morning Shift',
            'day_of_week' => 1, // Monday
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);
    }

    // ========== Authentication & Authorization Tests ==========

    public function test_unauthenticated_user_cannot_access_report(): void
    {
        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
        ]));

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_report(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
        ]));

        $response->assertStatus(200);
    }

    // ========== Validation Tests ==========

    public function test_start_date_is_required(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'end_date' => '2026-02-28',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_end_date_is_required(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => '2026-02-01',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_start_date_must_be_valid_date(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => 'invalid-date',
            'end_date' => '2026-02-28',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_start_date_must_be_before_or_equal_end_date(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => '2026-02-28',
            'end_date' => '2026-02-01',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_user_id_must_exist_if_provided(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'user_id' => 99999,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_machine_code_must_exist_if_provided(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'machine_code' => 'NONEXISTENT',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['machine_code']);
    }

    // ========== Functional Tests ==========

    public function test_can_get_empty_report_for_period_with_no_activities(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'period' => [
                        'start_date',
                        'end_date',
                        'total_days',
                    ],
                    'summary' => [
                        'total_users',
                        'total_shifts',
                        'completed_shifts',
                        'in_progress_shifts',
                        'not_started_shifts',
                        'total_work_hours',
                        'total_break_hours',
                        'total_incidents',
                        'average_work_hours_per_shift',
                    ],
                    'activities',
                ],
            ])
            ->assertJsonPath('data.summary.total_users', 0)
            ->assertJsonPath('data.summary.total_shifts', 0);
    }

    public function test_can_get_report_with_single_user_activity(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $date = '2026-02-17'; // Monday

        // Create shift assignment
        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker1->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $date,
            'machine_code' => $this->machine1->machine_code,
        ]);

        // Simulate clock in/out
        DB::table('machine_logs')->insert([
            'ulid' => (string) Str::ulid(),
            'user_id' => $this->worker1->id,
            'machine_code' => $this->machine1->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN->value,
            'log_message' => 'Clocked in',
            'created_at' => "$date 08:00:00",
            'updated_at' => "$date 08:00:00",
        ]);

        DB::table('machine_logs')->insert([
            'ulid' => (string) Str::ulid(),
            'user_id' => $this->worker1->id,
            'machine_code' => $this->machine1->machine_code,
            'event' => MachineLogEventEnum::CLOCK_OUT->value,
            'log_message' => 'Clocked out',
            'created_at' => "$date 16:00:00",
            'updated_at' => "$date 16:00:00",
        ]);

        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => $date,
            'end_date' => $date,
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.total_users', 1)
            ->assertJsonPath('data.summary.total_shifts', 1)
            ->assertJsonPath('data.summary.completed_shifts', 1)
            ->assertJsonPath('data.activities.0.user.id', $this->worker1->id)
            ->assertJsonPath('data.activities.0.user.name', 'Worker One')
            ->assertJsonPath('data.activities.0.daily_activities.0.date', $date)
            ->assertJsonPath('data.activities.0.daily_activities.0.shifts.0.attendance.status', 'completed')
            ->assertJsonPath('data.activities.0.daily_activities.0.shifts.0.work_summary.total_minutes', 480);
    }

    public function test_report_includes_multiple_users(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $date = '2026-02-17';

        // Worker 1 shift
        UserShift::factory()->create([
            'user_id' => $this->worker1->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $date,
            'machine_code' => $this->machine1->machine_code,
        ]);

        // Worker 2 shift
        UserShift::factory()->create([
            'user_id' => $this->worker2->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $date,
            'machine_code' => $this->machine2->machine_code,
        ]);

        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => $date,
            'end_date' => $date,
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.total_users', 2)
            ->assertJsonPath('data.summary.total_shifts', 2);
    }

    public function test_can_filter_report_by_user_id(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $date = '2026-02-17';

        // Worker 1 shift
        UserShift::factory()->create([
            'user_id' => $this->worker1->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $date,
            'machine_code' => $this->machine1->machine_code,
        ]);

        // Worker 2 shift
        UserShift::factory()->create([
            'user_id' => $this->worker2->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $date,
            'machine_code' => $this->machine2->machine_code,
        ]);

        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => $date,
            'end_date' => $date,
            'user_id' => $this->worker1->id,
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.total_users', 1)
            ->assertJsonPath('data.activities.0.user.id', $this->worker1->id);
    }

    public function test_can_filter_report_by_machine_code(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $date = '2026-02-17';

        // Worker 1 on machine 1
        UserShift::factory()->create([
            'user_id' => $this->worker1->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $date,
            'machine_code' => $this->machine1->machine_code,
        ]);

        // Worker 2 on machine 2
        UserShift::factory()->create([
            'user_id' => $this->worker2->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $date,
            'machine_code' => $this->machine2->machine_code,
        ]);

        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => $date,
            'end_date' => $date,
            'machine_code' => $this->machine1->machine_code,
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.total_users', 1)
            ->assertJsonPath('data.activities.0.daily_activities.0.shifts.0.machine.code', $this->machine1->machine_code);
    }

    public function test_report_includes_break_times(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $date = '2026-02-17';

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker1->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $date,
            'machine_code' => $this->machine1->machine_code,
        ]);

        // Clock in
        DB::table('machine_logs')->insert([
            'ulid' => (string) Str::ulid(),
            'user_id' => $this->worker1->id,
            'machine_code' => $this->machine1->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN->value,
            'log_message' => 'Clocked in',
            'created_at' => "$date 08:00:00",
            'updated_at' => "$date 08:00:00",
        ]);

        // Break start
        DB::table('machine_logs')->insert([
            'ulid' => (string) Str::ulid(),
            'user_id' => $this->worker1->id,
            'machine_code' => $this->machine1->machine_code,
            'event' => MachineLogEventEnum::BREAK_START->value,
            'log_message' => 'Break started',
            'created_at' => "$date 12:00:00",
            'updated_at' => "$date 12:00:00",
        ]);

        // Break end
        DB::table('machine_logs')->insert([
            'ulid' => (string) Str::ulid(),
            'user_id' => $this->worker1->id,
            'machine_code' => $this->machine1->machine_code,
            'event' => MachineLogEventEnum::BREAK_END->value,
            'log_message' => 'Break ended',
            'created_at' => "$date 12:30:00",
            'updated_at' => "$date 12:30:00",
        ]);

        // Clock out
        DB::table('machine_logs')->insert([
            'ulid' => (string) Str::ulid(),
            'user_id' => $this->worker1->id,
            'machine_code' => $this->machine1->machine_code,
            'event' => MachineLogEventEnum::CLOCK_OUT->value,
            'log_message' => 'Clocked out',
            'created_at' => "$date 16:00:00",
            'updated_at' => "$date 16:00:00",
        ]);

        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => $date,
            'end_date' => $date,
        ]));

        $response->assertStatus(200);
        
        $workSummary = $response->json('data.activities.0.daily_activities.0.shifts.0.work_summary');
        
        $this->assertEquals(480, $workSummary['total_minutes']); // 8 hours
        $this->assertEquals(30, $workSummary['break_minutes']); // 30 minutes break
        $this->assertEquals(450, $workSummary['actual_work_minutes']); // 7.5 hours actual work
        $this->assertCount(1, $workSummary['breaks']);
    }

    public function test_report_includes_machine_failures(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $date = '2026-02-17';

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker1->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $date,
            'machine_code' => $this->machine1->machine_code,
        ]);

        // Clock in
        DB::table('machine_logs')->insert([
            'ulid' => (string) Str::ulid(),
            'user_id' => $this->worker1->id,
            'machine_code' => $this->machine1->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN->value,
            'log_message' => 'Clocked in',
            'created_at' => "$date 08:00:00",
            'updated_at' => "$date 08:00:00",
        ]);

        // Machine failure
        DB::table('machine_logs')->insert([
            'ulid' => (string) Str::ulid(),
            'user_id' => $this->worker1->id,
            'machine_code' => $this->machine1->machine_code,
            'event' => MachineLogEventEnum::MACHINE_FAILURE->value,
            'log_message' => 'Machine overheating',
            'severity' => SeverityEnum::HIGH->value,
            'metadata' => json_encode(['temperature' => 95]),
            'created_at' => "$date 10:00:00",
            'updated_at' => "$date 10:00:00",
        ]);

        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => $date,
            'end_date' => $date,
        ]));

        $response->assertStatus(200);
        
        $incidents = $response->json('data.activities.0.daily_activities.0.shifts.0.incidents');
        
        $this->assertCount(1, $incidents['machine_failures']);
        $this->assertEquals('high', strtolower($incidents['machine_failures'][0]['severity']));
        $this->assertEquals('Machine overheating', $incidents['machine_failures'][0]['message']);
    }

    public function test_report_includes_machine_transfers(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $date = '2026-02-17';

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker1->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $date,
            'machine_code' => $this->machine1->machine_code,
        ]);

        // Clock in
        DB::table('machine_logs')->insert([
            'ulid' => (string) Str::ulid(),
            'user_id' => $this->worker1->id,
            'machine_code' => $this->machine1->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN->value,
            'log_message' => 'Clocked in',
            'created_at' => "$date 08:00:00",
            'updated_at' => "$date 08:00:00",
        ]);

        // Machine transfer
        DB::table('machine_logs')->insert([
            'ulid' => (string) Str::ulid(),
            'user_id' => $this->worker1->id,
            'machine_code' => $this->machine1->machine_code,
            'event' => MachineLogEventEnum::MACHINE_TRANSFER->value,
            'log_message' => 'Transferred to another machine',
            'metadata' => json_encode(['new_machine_code' => $this->machine2->machine_code, 'reason' => 'Emergency']),
            'created_at' => "$date 11:00:00",
            'updated_at' => "$date 11:00:00",
        ]);

        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => $date,
            'end_date' => $date,
        ]));

        $response->assertStatus(200);
        
        $incidents = $response->json('data.activities.0.daily_activities.0.shifts.0.incidents');
        
        $this->assertCount(1, $incidents['machine_transfers']);
        $this->assertEquals('Transferred to another machine', $incidents['machine_transfers'][0]['message']);
    }

    public function test_report_spans_multiple_days(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $monday = Shift::factory()->create([
            'name' => 'Monday Shift',
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $tuesday = Shift::factory()->create([
            'name' => 'Tuesday Shift',
            'day_of_week' => 2,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        // Day 1
        UserShift::factory()->create([
            'user_id' => $this->worker1->id,
            'shift_id' => $monday->id,
            'shift_date' => '2026-02-17',
            'machine_code' => $this->machine1->machine_code,
        ]);

        // Day 2
        UserShift::factory()->create([
            'user_id' => $this->worker1->id,
            'shift_id' => $tuesday->id,
            'shift_date' => '2026-02-18',
            'machine_code' => $this->machine1->machine_code,
        ]);

        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => '2026-02-17',
            'end_date' => '2026-02-18',
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('data.period.total_days', 2)
            ->assertJsonPath('data.summary.total_shifts', 2);
        
        $dailyActivities = $response->json('data.activities.0.daily_activities');
        $this->assertCount(2, $dailyActivities);
    }

    public function test_summary_calculations_are_accurate(): void
    {
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $date = '2026-02-17';

        // Worker 1 - completed shift
        $userShift1 = UserShift::factory()->create([
            'user_id' => $this->worker1->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $date,
            'machine_code' => $this->machine1->machine_code,
        ]);

        DB::table('machine_logs')->insert([
            'ulid' => (string) Str::ulid(),
            'user_id' => $this->worker1->id,
            'machine_code' => $this->machine1->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN->value,
            'log_message' => 'Clocked in',
            'created_at' => "$date 08:00:00",
            'updated_at' => "$date 08:00:00",
        ]);

        DB::table('machine_logs')->insert([
            'ulid' => (string) Str::ulid(),
            'user_id' => $this->worker1->id,
            'machine_code' => $this->machine1->machine_code,
            'event' => MachineLogEventEnum::CLOCK_OUT->value,
            'log_message' => 'Clocked out',
            'created_at' => "$date 16:00:00",
            'updated_at' => "$date 16:00:00",
        ]);

        // Worker 2 - not started
        UserShift::factory()->create([
            'user_id' => $this->worker2->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $date,
            'machine_code' => $this->machine2->machine_code,
        ]);

        $response = $this->getJson(route('api.backoffice.v1.report.user-machine-activity', [
            'start_date' => $date,
            'end_date' => $date,
        ]));

        $response->assertStatus(200);
        
        $summary = $response->json('data.summary');
        
        $this->assertEquals(2, $summary['total_users']);
        $this->assertEquals(2, $summary['total_shifts']);
        $this->assertEquals(1, $summary['completed_shifts']);
        $this->assertEquals(0, $summary['in_progress_shifts']);
        $this->assertEquals(1, $summary['not_started_shifts']);
        $this->assertEquals(8.0, $summary['total_work_hours']);
        $this->assertEquals(8.0, $summary['average_work_hours_per_shift']);
    }
}
