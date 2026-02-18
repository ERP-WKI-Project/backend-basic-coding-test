<?php

declare(strict_types=1);

namespace Tests\Feature\Machine;

use App\Enums\MachineLog\EventEnum;
use App\Enums\SystemAbility;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogEntryTest extends TestCase
{
    use RefreshDatabase;

    private function authenticateAsMachine(?User $user = null): User
    {
        $user ??= User::factory()->create();
        Sanctum::actingAs($user, [SystemAbility::MACHINE->value]);

        return $user;
    }

    private function createActiveShift(User $user, Machine $machine): UserShift
    {
        $shift = Shift::factory()->create([
            'day_of_week' => now()->dayOfWeekIso,
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
        ]);

        return UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'machine_id' => $machine->id,
            'shift_date' => now()->format('Y-m-d'),
        ]);
    }

    #[Test]
    public function it_can_list_log_entries_for_current_machine(): void
    {
        $user = $this->authenticateAsMachine();
        $machine = Machine::factory()->create();
        $this->createActiveShift($user, $machine);

        // Create logs for this machine
        MachineLog::factory()->count(5)->create([
            'machine_id' => $machine->id,
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/machine/v1/log-entry');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['ulid', 'machine', 'user', 'event', 'log_message', 'metadata', 'created_at'],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    #[Test]
    public function it_can_filter_logs_by_date_range(): void
    {
        $user = $this->authenticateAsMachine();
        $machine = Machine::factory()->create();
        $this->createActiveShift($user, $machine);

        MachineLog::factory()->create([
            'machine_id' => $machine->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(5),
        ]);

        MachineLog::factory()->create([
            'machine_id' => $machine->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->getJson('/api/machine/v1/log-entry?start_date='.now()->subDays(3)->format('Y-m-d').'&end_date='.now()->format('Y-m-d'));

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function it_can_filter_logs_by_event(): void
    {
        $user = $this->authenticateAsMachine();
        $machine = Machine::factory()->create();
        $this->createActiveShift($user, $machine);

        MachineLog::factory()->count(3)->create([
            'machine_id' => $machine->id,
            'user_id' => $user->id,
            'event' => EventEnum::PRODUCTION_START->value,
        ]);

        MachineLog::factory()->count(2)->create([
            'machine_id' => $machine->id,
            'user_id' => $user->id,
            'event' => EventEnum::PRODUCTION_END->value,
        ]);

        $response = $this->getJson('/api/machine/v1/log-entry?event='.EventEnum::PRODUCTION_START->value);

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    #[Test]
    public function it_only_shows_logs_for_current_machine(): void
    {
        $user = $this->authenticateAsMachine();
        $machine1 = Machine::factory()->create();
        $machine2 = Machine::factory()->create();
        $this->createActiveShift($user, $machine1);

        // Create logs for machine 1 (current)
        MachineLog::factory()->count(3)->create([
            'machine_id' => $machine1->id,
            'user_id' => $user->id,
        ]);

        // Create logs for machine 2 (different machine)
        MachineLog::factory()->count(5)->create([
            'machine_id' => $machine2->id,
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/machine/v1/log-entry');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    #[Test]
    public function it_returns_empty_when_no_logs(): void
    {
        $user = $this->authenticateAsMachine();
        $machine = Machine::factory()->create();
        $this->createActiveShift($user, $machine);

        $response = $this->getJson('/api/machine/v1/log-entry');

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_can_create_log_entry(): void
    {
        $user = $this->authenticateAsMachine();
        $machine = Machine::factory()->create();
        $this->createActiveShift($user, $machine);

        $response = $this->postJson('/api/machine/v1/log-entry', [
            'event' => EventEnum::PRODUCTION_START->value,
            'log_message' => 'Started batch #12345',
            'metadata' => ['batch_id' => '12345', 'product' => 'SKU-001'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.event', EventEnum::PRODUCTION_START->value)
            ->assertJsonPath('data.log_message', 'Started batch #12345')
            ->assertJsonPath('data.metadata.batch_id', '12345');

        $this->assertDatabaseHas('machine_logs', [
            'event' => EventEnum::PRODUCTION_START->value,
            'log_message' => 'Started batch #12345',
        ]);
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        $user = $this->authenticateAsMachine();
        $machine = Machine::factory()->create();
        $this->createActiveShift($user, $machine);

        $response = $this->postJson('/api/machine/v1/log-entry', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['event', 'log_message']);
    }

    #[Test]
    public function it_validates_event_enum(): void
    {
        $user = $this->authenticateAsMachine();
        $machine = Machine::factory()->create();
        $this->createActiveShift($user, $machine);

        $response = $this->postJson('/api/machine/v1/log-entry', [
            'event' => 'INVALID_EVENT',
            'log_message' => 'Test message',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['event']);
    }

    #[Test]
    public function it_automatically_sets_machine_from_user_session(): void
    {
        $user = $this->authenticateAsMachine();
        $machine = Machine::factory()->create();
        $this->createActiveShift($user, $machine);

        $response = $this->postJson('/api/machine/v1/log-entry', [
            'event' => EventEnum::PRODUCTION_START->value,
            'log_message' => 'Test message',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('machine_logs', [
            'machine_id' => $machine->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $response = $this->getJson('/api/machine/v1/log-entry');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_requires_machine_ability(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [SystemAbility::BACKOFFICE->value]);

        $response = $this->getJson('/api/machine/v1/log-entry');

        $response->assertForbidden();
    }

    #[Test]
    public function it_returns_empty_when_no_active_shift(): void
    {
        $user = $this->authenticateAsMachine();
        // Don't create active shift

        $response = $this->getJson('/api/machine/v1/log-entry');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_returns_403_when_creating_log_without_active_shift(): void
    {
        $user = $this->authenticateAsMachine();
        // Don't create active shift

        $response = $this->postJson('/api/machine/v1/log-entry', [
            'event' => EventEnum::PRODUCTION_START->value,
            'log_message' => 'Test message',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'No active shift for today');
    }
}
