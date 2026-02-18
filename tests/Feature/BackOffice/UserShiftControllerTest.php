<?php

declare(strict_types=1);

namespace Tests\Feature\BackOffice;

use App\Enums\SystemAbility;
use App\Models\Machine;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserShiftControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(): User
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin, [SystemAbility::BACKOFFICE->value]);

        return $admin;
    }

    #[Test]
    public function it_can_list_all_assignments_with_relations(): void
    {
        $this->authenticate();
        UserShift::factory()->count(5)->create();

        $response = $this->getJson('/api/backoffice/v1/user-shifts');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'user', 'shift', 'machine', 'shift_date'],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    #[Test]
    public function it_can_assign_shift_to_user(): void
    {
        $this->authenticate();
        $user = User::factory()->create();
        $shift = Shift::factory()->create();
        $machine = Machine::factory()->create();

        $response = $this->postJson('/api/backoffice/v1/user-shifts', [
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'machine_id' => $machine->id,
            'shift_date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertCreated()
            ->assertJson(fn ($json) => $json->where('data.user.ulid', $user->ulid)
                ->where('data.shift.ulid', $shift->ulid)
                ->where('data.machine.ulid', $machine->ulid)
                ->etc()
            );
    }

    #[Test]
    public function it_validates_user_not_found(): void
    {
        $this->authenticate();
        $shift = Shift::factory()->create();
        $machine = Machine::factory()->create();

        $response = $this->postJson('/api/backoffice/v1/user-shifts', [
            'user_id' => 99999,
            'shift_id' => $shift->id,
            'machine_id' => $machine->id,
            'shift_date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }

    #[Test]
    public function it_validates_shift_not_found(): void
    {
        $this->authenticate();
        $user = User::factory()->create();
        $machine = Machine::factory()->create();

        $response = $this->postJson('/api/backoffice/v1/user-shifts', [
            'user_id' => $user->id,
            'shift_id' => 99999,
            'machine_id' => $machine->id,
            'shift_date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['shift_id']);
    }

    #[Test]
    public function it_validates_machine_not_found(): void
    {
        $this->authenticate();
        $user = User::factory()->create();
        $shift = Shift::factory()->create();

        $response = $this->postJson('/api/backoffice/v1/user-shifts', [
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'machine_id' => 99999,
            'shift_date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['machine_id']);
    }

    #[Test]
    public function it_validates_date_in_past(): void
    {
        $this->authenticate();
        $user = User::factory()->create();
        $shift = Shift::factory()->create();
        $machine = Machine::factory()->create();

        $response = $this->postJson('/api/backoffice/v1/user-shifts', [
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'machine_id' => $machine->id,
            'shift_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['shift_date']);
    }

    #[Test]
    public function it_prevents_duplicate_assignment(): void
    {
        $this->authenticate();
        $user = User::factory()->create();
        $shift1 = Shift::factory()->create();
        $shift2 = Shift::factory()->create();
        $machine = Machine::factory()->create();
        $date = now()->addDay()->format('Y-m-d');

        // Create first assignment
        UserShift::factory()->forUser($user)->forShift($shift1)->forMachine($machine)->forDate($date)->create();

        // Try to create second assignment for same user on same date
        $response = $this->postJson('/api/backoffice/v1/user-shifts', [
            'user_id' => $user->id,
            'shift_id' => $shift2->id,
            'machine_id' => $machine->id,
            'shift_date' => $date,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['shift_date']);
    }

    #[Test]
    public function it_can_show_assignment_details(): void
    {
        $this->authenticate();
        $assignment = UserShift::factory()->create();

        $response = $this->getJson("/api/backoffice/v1/user-shifts/{$assignment->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $assignment->id);
    }

    #[Test]
    public function it_can_delete_assignment(): void
    {
        $this->authenticate();
        $assignment = UserShift::factory()->create();

        $response = $this->deleteJson("/api/backoffice/v1/user-shifts/{$assignment->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('user_shifts', ['id' => $assignment->id]);
    }
}
