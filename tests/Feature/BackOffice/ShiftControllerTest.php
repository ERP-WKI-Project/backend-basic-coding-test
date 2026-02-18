<?php

declare(strict_types=1);

namespace Tests\Feature\BackOffice;

use App\Enums\SystemAbility;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShiftControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(): User
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin, [SystemAbility::BACKOFFICE->value]);

        return $admin;
    }

    #[Test]
    public function it_can_list_all_shifts(): void
    {
        $this->authenticate();
        Shift::factory()->count(10)->create();

        $response = $this->getJson('/api/backoffice/v1/shifts');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['ulid', 'day_of_week', 'day_name', 'name', 'start_time', 'end_time'],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    #[Test]
    public function it_can_filter_shifts_by_day_of_week(): void
    {
        $this->authenticate();
        Shift::factory()->count(3)->create(['day_of_week' => 1]);
        Shift::factory()->count(2)->create(['day_of_week' => 2]);

        $response = $this->getJson('/api/backoffice/v1/shifts?day_of_week=1');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    #[Test]
    public function it_can_create_shift(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/backoffice/v1/shifts', [
            'day_of_week' => 1,
            'name' => 'Shift Pagi',
            'start_time' => '07:00:00',
            'end_time' => '15:00:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Shift Pagi')
            ->assertJsonPath('data.day_of_week', 1);

        $this->assertDatabaseHas('shifts', [
            'name' => 'Shift Pagi',
            'day_of_week' => 1,
        ]);
    }

    #[Test]
    public function it_validates_overlapping_times(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/backoffice/v1/shifts', [
            'day_of_week' => 1,
            'name' => 'Invalid Shift',
            'start_time' => '15:00:00',
            'end_time' => '07:00:00', // End before start
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_time']);
    }

    #[Test]
    public function it_can_show_shift_details(): void
    {
        $this->authenticate();
        $shift = Shift::factory()->create();

        $response = $this->getJson("/api/backoffice/v1/shifts/{$shift->ulid}");

        $response->assertOk()
            ->assertJsonPath('data.ulid', $shift->ulid)
            ->assertJsonPath('data.name', $shift->name);
    }

    #[Test]
    public function it_can_update_shift(): void
    {
        $this->authenticate();
        $shift = Shift::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/backoffice/v1/shifts/{$shift->ulid}", [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    #[Test]
    public function it_can_delete_shift(): void
    {
        $this->authenticate();
        $shift = Shift::factory()->create();

        $response = $this->deleteJson("/api/backoffice/v1/shifts/{$shift->ulid}");

        $response->assertOk();
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    #[Test]
    public function it_returns_401_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/backoffice/v1/shifts');

        $response->assertUnauthorized();
    }
}
