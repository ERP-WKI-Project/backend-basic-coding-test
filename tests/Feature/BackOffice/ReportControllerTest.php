<?php

declare(strict_types=1);

namespace Tests\Feature\BackOffice;

use App\Enums\SystemAbility;
use App\Models\Machine;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(): User
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin, [SystemAbility::BACKOFFICE->value]);

        return $admin;
    }

    private function createActivityData(): void
    {
        // Set a fixed date for consistency
        $date = '2026-02-15';

        $user1 = User::factory()->create(['employee_number' => '000001', 'name' => 'John Doe']);
        $user2 = User::factory()->create(['employee_number' => '000002', 'name' => 'Jane Smith']);
        $machine1 = Machine::factory()->create(['code' => 'MACHINE-001', 'name' => 'Machine 1']);
        $machine2 = Machine::factory()->create(['code' => 'MACHINE-002', 'name' => 'Machine 2']);

        // Create shifts
        $shift1 = Shift::factory()->create(['name' => 'Shift Pagi']);
        $shift2 = Shift::factory()->create(['name' => 'Shift Siang']);

        // Create user shifts using DB facade to control dates
        DB::table('user_shifts')->insert([
            [
                'user_id' => $user1->id,
                'machine_id' => $machine1->id,
                'shift_id' => $shift1->id,
                'shift_date' => $date,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user2->id,
                'machine_id' => $machine2->id,
                'shift_id' => $shift2->id,
                'shift_date' => $date,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Create machine logs using DB facade to control created_at
        DB::table('machine_logs')->insert([
            [
                'ulid' => (string) Str::ulid(),
                'machine_id' => $machine1->id,
                'user_id' => $user1->id,
                'event' => 'LOGIN',
                'log_message' => 'Login',
                'metadata' => null,
                'created_at' => $date.' 07:00:00',
                'updated_at' => $date.' 07:00:00',
            ],
            [
                'ulid' => (string) Str::ulid(),
                'machine_id' => $machine1->id,
                'user_id' => $user1->id,
                'event' => 'PRODUCTION_START',
                'log_message' => 'Start production',
                'metadata' => null,
                'created_at' => $date.' 07:30:00',
                'updated_at' => $date.' 07:30:00',
            ],
            [
                'ulid' => (string) Str::ulid(),
                'machine_id' => $machine1->id,
                'user_id' => $user1->id,
                'event' => 'LOGOUT',
                'log_message' => 'Logout',
                'metadata' => null,
                'created_at' => $date.' 15:00:00',
                'updated_at' => $date.' 15:00:00',
            ],
            [
                'ulid' => (string) Str::ulid(),
                'machine_id' => $machine2->id,
                'user_id' => $user2->id,
                'event' => 'LOGIN',
                'log_message' => 'Login',
                'metadata' => null,
                'created_at' => $date.' 15:00:00',
                'updated_at' => $date.' 15:00:00',
            ],
            [
                'ulid' => (string) Str::ulid(),
                'machine_id' => $machine2->id,
                'user_id' => $user2->id,
                'event' => 'LOGOUT',
                'log_message' => 'Logout',
                'metadata' => null,
                'created_at' => $date.' 23:00:00',
                'updated_at' => $date.' 23:00:00',
            ],
        ]);
    }

    #[Test]
    public function it_can_generate_activity_report(): void
    {
        $this->authenticate();
        $this->createActivityData();

        $response = $this->getJson('/api/backoffice/v1/reports/user-machine-activity?start_date=2026-02-15&end_date=2026-02-15');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'summary' => ['total_users', 'total_machines', 'total_activities'],
                    'activities' => [
                        '*' => ['date', 'user', 'machine', 'shift', 'login_time', 'logout_time', 'duration_hours', 'total_log_entries'],
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_includes_summary_statistics(): void
    {
        $this->authenticate();
        $this->createActivityData();

        $response = $this->getJson('/api/backoffice/v1/reports/user-machine-activity?start_date=2026-02-15&end_date=2026-02-15');

        $response->assertOk()
            ->assertJsonPath('data.summary.total_users', 2)
            ->assertJsonPath('data.summary.total_machines', 2)
            ->assertJsonPath('data.summary.total_activities', 2);
    }

    #[Test]
    public function it_calculates_login_logout_times(): void
    {
        $this->authenticate();
        $this->createActivityData();

        $response = $this->getJson('/api/backoffice/v1/reports/user-machine-activity?start_date=2026-02-15&end_date=2026-02-15');

        $response->assertOk();

        $activities = $response->json('data.activities');
        $user1Activity = collect($activities)->first(fn ($a) => $a['user']['employee_number'] === '000001');

        $this->assertNotNull($user1Activity);
        $this->assertEquals('07:00:00', $user1Activity['login_time']);
        $this->assertEquals('15:00:00', $user1Activity['logout_time']);
    }

    #[Test]
    public function it_calculates_duration_hours(): void
    {
        $this->authenticate();
        $this->createActivityData();

        $response = $this->getJson('/api/backoffice/v1/reports/user-machine-activity?start_date=2026-02-15&end_date=2026-02-15');

        $response->assertOk();

        $activities = $response->json('data.activities');
        $user1Activity = collect($activities)->first(fn ($a) => $a['user']['employee_number'] === '000001');

        $this->assertNotNull($user1Activity);
        $this->assertEquals(8, $user1Activity['duration_hours']);
    }

    #[Test]
    public function it_can_filter_by_date_range(): void
    {
        $this->authenticate();
        $this->createActivityData();

        // Test with wider date range
        $response = $this->getJson('/api/backoffice/v1/reports/user-machine-activity?start_date=2026-02-01&end_date=2026-02-28');

        $response->assertOk()
            ->assertJsonPath('data.summary.total_activities', 2);

        // Test with date range that excludes data
        $response = $this->getJson('/api/backoffice/v1/reports/user-machine-activity?start_date=2026-01-01&end_date=2026-01-31');

        $response->assertOk()
            ->assertJsonPath('data.summary.total_activities', 0);
    }

    #[Test]
    public function it_can_filter_by_user_id(): void
    {
        $this->authenticate();
        $this->createActivityData();

        $user1 = User::where('employee_number', '000001')->first();

        $response = $this->getJson("/api/backoffice/v1/reports/user-machine-activity?start_date=2026-02-15&end_date=2026-02-15&user_id={$user1->id}");

        $response->assertOk()
            ->assertJsonPath('data.summary.total_users', 1)
            ->assertJsonPath('data.summary.total_activities', 1);
    }

    #[Test]
    public function it_can_filter_by_machine_id(): void
    {
        $this->authenticate();
        $this->createActivityData();

        $machine1 = Machine::where('code', 'MACHINE-001')->first();

        $response = $this->getJson("/api/backoffice/v1/reports/user-machine-activity?start_date=2026-02-15&end_date=2026-02-15&machine_id={$machine1->id}");

        $response->assertOk()
            ->assertJsonPath('data.summary.total_machines', 1)
            ->assertJsonPath('data.summary.total_activities', 1);
    }

    #[Test]
    public function it_validates_required_date_parameters(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/backoffice/v1/reports/user-machine-activity');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    #[Test]
    public function it_validates_date_format(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/backoffice/v1/reports/user-machine-activity?start_date=15-02-2026&end_date=invalid');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    #[Test]
    public function it_returns_empty_when_no_data(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/backoffice/v1/reports/user-machine-activity?start_date=2026-01-01&end_date=2026-01-31');

        $response->assertOk()
            ->assertJsonPath('data.summary.total_users', 0)
            ->assertJsonPath('data.summary.total_machines', 0)
            ->assertJsonPath('data.summary.total_activities', 0)
            ->assertJsonCount(0, 'data.activities');
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $response = $this->getJson('/api/backoffice/v1/reports/user-machine-activity?start_date=2026-02-01&end_date=2026-02-28');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_requires_backoffice_ability(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [SystemAbility::MACHINE->value]);

        $response = $this->getJson('/api/backoffice/v1/reports/user-machine-activity?start_date=2026-02-01&end_date=2026-02-28');

        $response->assertForbidden();
    }
}
