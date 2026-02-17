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

class UserShiftAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $worker;
    private Machine $machine;
    private Shift $mondayMorningShift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
        
        $this->worker = User::factory()->create();
        $this->machine = Machine::factory()->create();
        
        // Monday Morning Shift: 08:00-16:00
        $this->mondayMorningShift = Shift::factory()->create([
            'name' => 'Monday Morning',
            'day_of_week' => 1, // Monday
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);
    }

    // ========== Assignment Tests ==========

    public function test_can_assign_user_to_shift(): void
    {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.store'), [
                'user_id' => $this->worker->id,
                'shift_id' => $this->mondayMorningShift->id,
                'shift_date' => $nextMonday,
                'machine_code' => $this->machine->machine_code,
                'notes' => 'Regular assignment',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.id', $this->worker->id)
            ->assertJsonPath('data.machine.machine_code', $this->machine->machine_code);

        $this->assertDatabaseHas('user_shifts', [
            'user_id' => $this->worker->id,
            'machine_code' => $this->machine->machine_code,
        ]);
    }

    public function test_cannot_assign_user_to_wrong_day_of_week(): void
    {
        $nextTuesday = Carbon::now()->next(Carbon::TUESDAY)->format('Y-m-d');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.store'), [
                'user_id' => $this->worker->id,
                'shift_id' => $this->mondayMorningShift->id, // Monday shift
                'shift_date' => $nextTuesday, // Tuesday date
                'machine_code' => $this->machine->machine_code,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_date']);
    }

    public function test_cannot_double_book_user(): void
    {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        // First assignment
        $firstAssignment = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $this->mondayMorningShift->id,
            'shift_date' => $nextMonday,
            'machine_code' => $this->machine->machine_code,
        ]);

        // Create another shift with overlapping time
        $overlappingShift = Shift::factory()->create([
            'day_of_week' => 1,
            'start_time' => '14:00', // Overlaps with 08:00-16:00
            'end_time' => '22:00',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.store'), [
                'user_id' => $this->worker->id,
                'shift_id' => $overlappingShift->id,
                'shift_date' => $nextMonday,
                'machine_code' => Machine::factory()->create()->machine_code,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_user_can_work_different_shifts_same_day_no_overlap(): void
    {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        // First shift: 08:00-12:00
        $morningShift = Shift::factory()->create([
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $morningShift->id,
            'shift_date' => $nextMonday,
            'machine_code' => $this->machine->machine_code,
        ]);

        // Second shift: 13:00-17:00 (no overlap)
        $afternoonShift = Shift::factory()->create([
            'day_of_week' => 1,
            'start_time' => '13:00',
            'end_time' => '17:00',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.store'), [
                'user_id' => $this->worker->id,
                'shift_id' => $afternoonShift->id,
                'shift_date' => $nextMonday,
                'machine_code' => Machine::factory()->create()->machine_code,
            ]);

        $response->assertStatus(201);
    }

    public function test_cannot_assign_same_machine_to_multiple_users_overlapping_time(): void
    {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        // User 1 on Machine A
        UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $this->mondayMorningShift->id,
            'shift_date' => $nextMonday,
            'machine_code' => $this->machine->machine_code,
        ]);

        // Try to assign User 2 to same machine, same day, overlapping time
        $worker2 = User::factory()->create();
        
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.store'), [
                'user_id' => $worker2->id,
                'shift_id' => $this->mondayMorningShift->id,
                'shift_date' => $nextMonday,
                'machine_code' => $this->machine->machine_code, // Same machine
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['machine_code']);
    }

    public function test_machine_can_be_used_by_different_users_in_non_overlapping_shifts(): void
    {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        // User 1: Morning 08:00-12:00
        $morningShift = Shift::factory()->create([
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $morningShift->id,
            'shift_date' => $nextMonday,
            'machine_code' => $this->machine->machine_code,
        ]);

        // User 2: Afternoon 13:00-17:00 (same machine, no overlap)
        $afternoonShift = Shift::factory()->create([
            'day_of_week' => 1,
            'start_time' => '13:00',
            'end_time' => '17:00',
        ]);

        $worker2 = User::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.store'), [
                'user_id' => $worker2->id,
                'shift_id' => $afternoonShift->id,
                'shift_date' => $nextMonday,
                'machine_code' => $this->machine->machine_code, // Same machine
            ]);

        $response->assertStatus(201);
    }

    public function test_machine_code_is_required(): void
    {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.store'), [
                'user_id' => $this->worker->id,
                'shift_id' => $this->mondayMorningShift->id,
                'shift_date' => $nextMonday,
                // machine_code missing
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['machine_code']);
    }

    // ========== Clock In/Out Tests ==========

    public function test_user_can_clock_in(): void
    {
        $today = Carbon::now()->format('Y-m-d');
        $todayDayOfWeek = Carbon::now()->dayOfWeek;

        $shift = Shift::factory()->create([
            'day_of_week' => $todayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $shift->id,
            'shift_date' => $today,
            'machine_code' => $this->machine->machine_code,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.clock-in', $userShift->id));

        $response->assertStatus(201)
            ->assertJsonPath('data.event', MachineLogEventEnum::CLOCK_IN->value);

        $this->assertDatabaseHas('machine_logs', [
            'user_id' => $this->worker->id,
            'machine_code' => $this->machine->machine_code,
            'event' => MachineLogEventEnum::CLOCK_IN->value,
        ]);
    }

    public function test_user_can_clock_out(): void
    {
        $today = Carbon::now()->format('Y-m-d');
        $todayDayOfWeek = Carbon::now()->dayOfWeek;

        $shift = Shift::factory()->create([
            'day_of_week' => $todayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $shift->id,
            'shift_date' => $today,
            'machine_code' => $this->machine->machine_code,
        ]);

        // Clock in first
        MachineLog::create([
            'machine_code' => $this->machine->machine_code,
            'user_id' => $this->worker->id,
            'event' => MachineLogEventEnum::CLOCK_IN,
            'log_message' => 'Clocked in',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.clock-out', $userShift->id));

        $response->assertStatus(201)
            ->assertJsonPath('data.event', MachineLogEventEnum::CLOCK_OUT->value);
    }

    public function test_cannot_clock_out_without_clock_in(): void
    {
        $today = Carbon::now()->format('Y-m-d');
        $todayDayOfWeek = Carbon::now()->dayOfWeek;

        $shift = Shift::factory()->create([
            'day_of_week' => $todayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $shift->id,
            'shift_date' => $today,
            'machine_code' => $this->machine->machine_code,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.clock-out', $userShift->id));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['clock_out']);
    }

    // ========== Machine Transfer Tests ==========

    public function test_can_transfer_machine_during_active_shift(): void
    {
        $today = Carbon::now()->format('Y-m-d');
        $todayDayOfWeek = Carbon::now()->dayOfWeek;

        $shift = Shift::factory()->create([
            'day_of_week' => $todayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $shift->id,
            'shift_date' => $today,
            'machine_code' => $this->machine->machine_code,
        ]);

        // Clock in first
        MachineLog::create([
            'machine_code' => $this->machine->machine_code,
            'user_id' => $this->worker->id,
            'event' => MachineLogEventEnum::CLOCK_IN,
            'log_message' => 'Clocked in',
        ]);

        $newMachine = Machine::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.transfer-machine', $userShift->id), [
                'new_machine_code' => $newMachine->machine_code,
                'reason' => 'Machine breakdown',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.machine.machine_code', $newMachine->machine_code);

        $this->assertDatabaseHas('user_shifts', [
            'id' => $userShift->id,
            'machine_code' => $newMachine->machine_code,
        ]);

        $this->assertDatabaseHas('machine_logs', [
            'event' => MachineLogEventEnum::MACHINE_TRANSFER->value,
        ]);
    }

    public function test_cannot_transfer_machine_before_clock_in(): void
    {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $this->mondayMorningShift->id,
            'shift_date' => $nextMonday,
            'machine_code' => $this->machine->machine_code,
        ]);

        $newMachine = Machine::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.transfer-machine', $userShift->id), [
                'new_machine_code' => $newMachine->machine_code,
                'reason' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['transfer']);
    }

    public function test_cannot_transfer_to_occupied_machine(): void
    {
        $today = Carbon::now()->format('Y-m-d');
        $todayDayOfWeek = Carbon::now()->dayOfWeek;

        $shift = Shift::factory()->create([
            'day_of_week' => $todayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        // User 1 on Machine 1
        $userShift1 = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $shift->id,
            'shift_date' => $today,
            'machine_code' => $this->machine->machine_code,
        ]);

        MachineLog::create([
            'machine_code' => $this->machine->machine_code,
            'user_id' => $this->worker->id,
            'event' => MachineLogEventEnum::CLOCK_IN,
            'log_message' => 'Clocked in',
        ]);

        // User 2 on Machine 2
        $worker2 = User::factory()->create();
        $machine2 = Machine::factory()->create();

        $userShift2 = UserShift::factory()->create([
            'user_id' => $worker2->id,
            'shift_id' => $shift->id,
            'shift_date' => $today,
            'machine_code' => $machine2->machine_code,
        ]);

        MachineLog::create([
            'machine_code' => $machine2->machine_code,
            'user_id' => $worker2->id,
            'event' => MachineLogEventEnum::CLOCK_IN,
            'log_message' => 'Clocked in',
        ]);

        // User 1 tries to transfer to Machine 2 (occupied by User 2)
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.transfer-machine', $userShift1->id), [
                'new_machine_code' => $machine2->machine_code,
                'reason' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['machine_code']);
    }

    // ========== Break Time Tests ==========

    public function test_user_can_start_break(): void
    {
        $today = Carbon::now()->format('Y-m-d');
        $todayDayOfWeek = Carbon::now()->dayOfWeek;

        $shift = Shift::factory()->create([
            'day_of_week' => $todayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $shift->id,
            'shift_date' => $today,
            'machine_code' => $this->machine->machine_code,
        ]);

        // Clock in first
        MachineLog::create([
            'machine_code' => $this->machine->machine_code,
            'user_id' => $this->worker->id,
            'event' => MachineLogEventEnum::CLOCK_IN,
            'log_message' => 'Clocked in',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.start-break', $userShift->id), [
                'break_reason' => 'Lunch break',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.event', MachineLogEventEnum::BREAK_START->value);
    }

    public function test_user_can_end_break(): void
    {
        $today = Carbon::now()->format('Y-m-d');
        $todayDayOfWeek = Carbon::now()->dayOfWeek;

        $shift = Shift::factory()->create([
            'day_of_week' => $todayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $shift->id,
            'shift_date' => $today,
            'machine_code' => $this->machine->machine_code,
        ]);

        // Clock in and start break
        MachineLog::create([
            'machine_code' => $this->machine->machine_code,
            'user_id' => $this->worker->id,
            'event' => MachineLogEventEnum::CLOCK_IN,
            'log_message' => 'Clocked in',
        ]);

        MachineLog::create([
            'machine_code' => $this->machine->machine_code,
            'user_id' => $this->worker->id,
            'event' => MachineLogEventEnum::BREAK_START,
            'log_message' => 'Break started',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.end-break', $userShift->id));

        $response->assertStatus(201)
            ->assertJsonPath('data.event', MachineLogEventEnum::BREAK_END->value);
    }

    // ========== Machine Failure Tests ==========

    public function test_can_report_machine_failure(): void
    {
        $today = Carbon::now()->format('Y-m-d');
        $todayDayOfWeek = Carbon::now()->dayOfWeek;

        $shift = Shift::factory()->create([
            'day_of_week' => $todayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $shift->id,
            'shift_date' => $today,
            'machine_code' => $this->machine->machine_code,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.user-shift.report-failure', $userShift->id), [
                'failure_description' => 'Motor overheating, smoke detected',
                'severity' => 'high',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.event', MachineLogEventEnum::MACHINE_FAILURE->value)
            ->assertJsonPath('data.severity', SeverityEnum::HIGH->value);

        $this->assertDatabaseHas('machine_logs', [
            'event' => MachineLogEventEnum::MACHINE_FAILURE->value,
            'severity' => SeverityEnum::HIGH->value,
        ]);
    }

    // ========== Update/Delete Tests ==========

    public function test_cannot_update_started_shift(): void
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $yesterdayDayOfWeek = Carbon::yesterday()->dayOfWeek;

        $shift = Shift::factory()->create([
            'day_of_week' => $yesterdayDayOfWeek,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $shift->id,
            'shift_date' => $yesterday,
            'machine_code' => $this->machine->machine_code,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson(route('api.backoffice.v1.user-shift.update', $userShift->id), [
                'notes' => 'Trying to update',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift']);
    }

    public function test_cannot_delete_assignment_with_logs(): void
    {
        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'machine_code' => $this->machine->machine_code,
        ]);

        // Create machine log (user has worked) - using DB::table to set created_at
        DB::table('machine_logs')->insert([
            'ulid' => (string) Str::ulid(),
            'machine_code' => $this->machine->machine_code,
            'user_id' => $this->worker->id,
            'event' => MachineLogEventEnum::CLOCK_IN->value,
            'log_message' => 'Worked',
            'created_at' => $userShift->shift_date,
            'updated_at' => $userShift->shift_date,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson(route('api.backoffice.v1.user-shift.destroy', $userShift->id));

        $response->assertStatus(422);
        $this->assertDatabaseHas('user_shifts', ['id' => $userShift->id]);
    }

    public function test_can_delete_assignment_without_logs(): void
    {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        $userShift = UserShift::factory()->create([
            'user_id' => $this->worker->id,
            'shift_id' => $this->mondayMorningShift->id,
            'shift_date' => $nextMonday,
            'machine_code' => $this->machine->machine_code,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson(route('api.backoffice.v1.user-shift.destroy', $userShift->id));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('user_shifts', ['id' => $userShift->id]);
    }

    // ========== Query Tests ==========

    public function test_can_get_user_schedule(): void
    {
        $startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
        $endDate = Carbon::now()->endOfWeek()->format('Y-m-d');

        // Create multiple assignments for the week
        for ($i = 0; $i < 5; $i++) {
            $date = Carbon::now()->startOfWeek()->addDays($i);
            $shift = Shift::factory()->create([
                'day_of_week' => $date->dayOfWeek,
                'start_time' => '08:00',
                'end_time' => '16:00',
            ]);

            UserShift::factory()->create([
                'user_id' => $this->worker->id,
                'shift_id' => $shift->id,
                'shift_date' => $date->format('Y-m-d'),
                'machine_code' => $this->machine->machine_code,
            ]);
        }

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson(route('api.backoffice.v1.user-shift.user-schedule', [
                'user_id' => $this->worker->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]));

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }
}
