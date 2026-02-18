<?php

declare(strict_types=1);

namespace Tests\Feature\BackOffice;

use App\Enums\SystemAbility;
use App\Models\Machine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MachineControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(): User
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin, [SystemAbility::BACKOFFICE->value]);

        return $admin;
    }

    // ==================== INDEX TESTS ====================

    #[Test]
    public function it_can_list_all_machines(): void
    {
        $this->authenticate();
        Machine::factory()->count(10)->create();

        $response = $this->getJson('/api/backoffice/v1/machines');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['ulid', 'code', 'name', 'location', 'status', 'status_label'],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    #[Test]
    public function it_can_filter_machines_by_status(): void
    {
        $this->authenticate();
        Machine::factory()->count(3)->active()->create();
        Machine::factory()->count(2)->maintenance()->create();

        $response = $this->getJson('/api/backoffice/v1/machines?status=active');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    // ==================== STORE TESTS ====================

    #[Test]
    public function it_can_create_machine(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/backoffice/v1/machines', [
            'code' => 'TEST-MACHINE-001',
            'name' => 'Test Machine',
            'location' => 'Plant A',
            'status' => 'active',
            'description' => 'Test description',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'TEST-MACHINE-001')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.status_label', 'Aktif');

        $this->assertDatabaseHas('machines', [
            'code' => 'TEST-MACHINE-001',
            'name' => 'Test Machine',
        ]);
    }

    #[Test]
    public function it_validates_machine_code_format(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/backoffice/v1/machines', [
            'code' => 'lowercase-code', // Invalid: must be uppercase
            'name' => 'Test Machine',
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    #[Test]
    public function it_prevents_duplicate_machine_code(): void
    {
        $this->authenticate();
        Machine::factory()->create(['code' => 'DUPLICATE-001']);

        $response = $this->postJson('/api/backoffice/v1/machines', [
            'code' => 'DUPLICATE-001',
            'name' => 'Another Machine',
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    // ==================== SHOW TESTS ====================

    #[Test]
    public function it_can_show_machine_details(): void
    {
        $this->authenticate();
        $machine = Machine::factory()->create();

        $response = $this->getJson("/api/backoffice/v1/machines/{$machine->ulid}");

        $response->assertOk()
            ->assertJsonPath('data.ulid', $machine->ulid)
            ->assertJsonPath('data.code', $machine->code);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_machine(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/backoffice/v1/machines/invalid-ulid');

        $response->assertNotFound();
    }

    // ==================== UPDATE TESTS ====================

    #[Test]
    public function it_can_update_machine_status(): void
    {
        $this->authenticate();
        $machine = Machine::factory()->active()->create();

        $response = $this->putJson("/api/backoffice/v1/machines/{$machine->ulid}", [
            'status' => 'maintenance',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'maintenance')
            ->assertJsonPath('data.status_label', 'Dalam Perbaikan');
    }

    // ==================== DELETE TESTS ====================

    #[Test]
    public function it_can_delete_unused_machine(): void
    {
        $this->authenticate();
        $machine = Machine::factory()->create();

        $response = $this->deleteJson("/api/backoffice/v1/machines/{$machine->ulid}");

        $response->assertOk();
        $this->assertDatabaseMissing('machines', ['id' => $machine->id]);
    }

    #[Test]
    public function it_returns_404_when_deleting_nonexistent_machine(): void
    {
        $this->authenticate();

        $response = $this->deleteJson('/api/backoffice/v1/machines/invalid-ulid');

        $response->assertNotFound();
    }
}
