<?php

namespace Tests\Feature;

use App\Enums\SystemAbility;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShiftManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        Sanctum::actingAs($this->admin, [SystemAbility::BACKOFFICE->value]);
    }

    public function test_can_create_shift_successfully(): void
    {
        $response = $this->postJson(route('api.backoffice.v1.shift..store'), [
                'name' => 'Morning Shift',
                'day_of_week' => 1, // Monday
                'start_time' => '08:00',
                'end_time' => '16:00',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'day_of_week', 'start_time', 'end_time'],
            ])
            ->assertJsonPath('data.name', 'Morning Shift');

        $this->assertDatabaseHas('shifts', [
            'name' => 'Morning Shift',
            'day_of_week' => 1,
        ]);
    }

    public function test_cannot_create_overlapping_shifts(): void
    {
        // Create first shift: Monday 08:00-16:00
        Shift::factory()->create([
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        // Try to create overlapping shift: Monday 14:00-22:00 (overlaps with 08:00-16:00)
        $response = $this->postJson(route('api.backoffice.v1.shift..store'), [
                'name' => 'Afternoon Shift',
                'day_of_week' => 1,
                'start_time' => '14:00',
                'end_time' => '22:00',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['time']);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson(route('api.backoffice.v1.shift..store'), [
                'name' => 'Invalid Shift',
                'day_of_week' => 1,
                'start_time' => '16:00',
                'end_time' => '08:00', // Before start time
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);
    }

    public function test_day_of_week_must_be_valid(): void
    {
        $response = $this->postJson(route('api.backoffice.v1.shift..store'), [
                'name' => 'Invalid Day Shift',
                'day_of_week' => 7, // Invalid (must be 0-6)
                'start_time' => '08:00',
                'end_time' => '16:00',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['day_of_week']);
    }

    public function test_can_update_shift(): void
    {
        $shift = Shift::factory()->create([
            'name' => 'Old Shift',
            'day_of_week' => 1,
        ]);

        $response = $this->putJson(route('api.backoffice.v1.shift..update', $shift->id), [
                'name' => 'Updated Shift',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Shift');
    }

    public function test_can_delete_shift_without_assignments(): void
    {
        $shift = Shift::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson(route('api.backoffice.v1.shift..destroy', $shift->id));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    public function test_cannot_delete_shift_with_assignments(): void
    {
        $shift = Shift::factory()->create();
        UserShift::factory()->create(['shift_id' => $shift->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson(route('api.backoffice.v1.shift..destroy', $shift->id));

        $response->assertStatus(422);
        $this->assertDatabaseHas('shifts', ['id' => $shift->id]);
    }

    public function test_can_view_all_shifts(): void
    {
        Shift::factory()->count(3)->create();

        $response = $this->getJson(route('api.backoffice.v1.shift..index'));

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_view_single_shift(): void
    {
        $shift = Shift::factory()->create(['name' => 'Test Shift']);

        $response = $this->getJson(route('api.backoffice.v1.shift..show', $shift->id));

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Test Shift');
    }

    public function test_shifts_on_different_days_can_overlap(): void
    {
        // Monday 08:00-16:00
        Shift::factory()->create([
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        // Tuesday 08:00-16:00 (same time but different day - should be OK)
        $response = $this->postJson(route('api.backoffice.v1.shift..store'), [
                'name' => 'Tuesday Morning',
                'day_of_week' => 2,
                'start_time' => '08:00',
                'end_time' => '16:00',
            ]);

        $response->assertStatus(201);
    }
}