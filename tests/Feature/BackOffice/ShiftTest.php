<?php

namespace Tests\Feature\BackOffice;

use App\Enums\SystemAbility;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Database\Seeders\PresetForCodingTestSeeder;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->seed(PresetForCodingTestSeeder::class);

    $this->adminUser = User::where('employee_number', '000001')->first();

    if (!$this->adminUser) {
        $this->adminUser = User::factory()->create([
            'employee_number' => '000001',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
    }
});

describe('Shift Index', function () {
    test('shift_index_returns_paginated_shifts', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = getJson('/api/backoffice/v1/shift');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    test('shift_index_with_limit', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = getJson('/api/backoffice/v1/shift?limit=5');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 5);
    });

    test('shift_index_with_search', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = getJson('/api/backoffice/v1/shift?search=Shift Pagi');

        $response->assertStatus(200);
    });

    test('shift_index_filter_by_day_of_week', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = getJson('/api/backoffice/v1/shift?day_of_week=1');

        $response->assertStatus(200);
    });
});

describe('Shift Store', function () {
    test('shift_store_success', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = postJson('/api/backoffice/v1/shift', [
            'name' => 'Shift Baru',
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Shift Baru',
                'day_of_week' => 1,
            ]);

        $this->assertDatabaseHas('shifts', [
            'name' => 'Shift Baru',
            'day_of_week' => 1,
        ]);
    });

    test('shift_store_validation_failed', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = postJson('/api/backoffice/v1/shift', [
            'name' => '',
            'day_of_week' => 8,
            'start_time' => 'invalid',
            'end_time' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'day_of_week', 'start_time', 'end_time']);
    });

    test('shift_store_end_time_must_be_after_start_time', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = postJson('/api/backoffice/v1/shift', [
            'name' => 'Shift Salah',
            'day_of_week' => 1,
            'start_time' => '16:00:00',
            'end_time' => '08:00:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);
    });
});

describe('Shift Show', function () {
    test('shift_show_success', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $shift = Shift::first();

        $response = getJson("/api/backoffice/v1/shift/{$shift->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $shift->id,
                'name' => $shift->name,
            ]);
    });

    test('shift_show_not_found', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = getJson('/api/backoffice/v1/shift/999999');

        $response->assertStatus(404);
    });
});

describe('Shift Update', function () {
    test('shift_update_success', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $shift = Shift::first();

        $response = putJson("/api/backoffice/v1/shift/{$shift->id}", [
            'name' => 'Shift Diperbarui',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Shift Diperbarui']);

        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'name' => 'Shift Diperbarui',
        ]);
    });

    test('shift_update_not_found', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = putJson('/api/backoffice/v1/shift/999999', [
            'name' => 'Updated',
        ]);

        $response->assertStatus(404);
    });
});

describe('Shift Delete', function () {
    test('shift_delete_success', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $shift = Shift::create([
            'ulid' => Str::ulid(),
            'name' => 'Shift untuk Dihapus',
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $response = deleteJson("/api/backoffice/v1/shift/{$shift->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Shift berhasil dihapus.']);

        $this->assertSoftDeleted('shifts', ['id' => $shift->id]);
    });

    test('shift_delete_not_found', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = deleteJson('/api/backoffice/v1/shift/999999');

        $response->assertStatus(404);
    });
});

describe('Auth & Authorization', function () {
    test('shift_unauthenticated_redirects', function () {
        $response = getJson('/api/backoffice/v1/shift');

        $this->assertContains($response->getStatusCode(), [401, 403, 500]);
    });

    test('shift_wrong_ability', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::MACHINE->value]);

        $response = getJson('/api/backoffice/v1/shift');

        $response->assertStatus(403);
    });
});

describe('User Shift Management', function () {
    test('user_shift_assign_success', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user = User::first();
        $shift = Shift::first();

        $response = postJson('/api/backoffice/v1/user-shift', [
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'shift_date' => now()->format('Y-m-d'),
            'machine_code' => 'FILLING-MACHINE-001',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'user_id' => $user->id,
                'shift_id' => $shift->id,
            ]);

        $this->assertDatabaseHas('user_shifts', [
            'user_id' => $user->id,
            'shift_id' => $shift->id,
        ]);
    });

    test('user_shift_assign_validation_failed', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = postJson('/api/backoffice/v1/user-shift', [
            'user_id' => 999999,
            'shift_id' => 999999,
            'shift_date' => 'invalid-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'shift_id', 'shift_date']);
    });

    test('user_shift_update_success', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user = User::first();
        $shift = Shift::first();

        $userShift = UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'shift_date' => now()->format('Y-m-d'),
            'machine_code' => 'FILLING-MACHINE-001',
        ]);

        $newShift = Shift::where('id', '!=', $shift->id)->first();

        $response = putJson("/api/backoffice/v1/user-shift/{$userShift->id}", [
            'user_id' => $user->id,
            'shift_id' => $newShift->id,
            'shift_date' => now()->format('Y-m-d'),
            'machine_code' => 'NEW-MACHINE-001',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'shift_id' => $newShift->id,
                'machine_code' => 'NEW-MACHINE-001',
            ]);
    });

    test('user_shift_delete_success', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user = User::first();
        $shift = Shift::first();

        $userShift = UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'shift_date' => now()->format('Y-m-d'),
            'machine_code' => 'FILLING-MACHINE-001',
        ]);

        $response = deleteJson("/api/backoffice/v1/user-shift/{$userShift->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Shift pengguna berhasil dihapus.']);
    });

    test('user_shift_index_with_filters', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user = User::first();
        $shift = Shift::first();

        UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'shift_date' => now()->format('Y-m-d'),
            'machine_code' => 'FILLING-MACHINE-001',
        ]);

        $response = getJson("/api/backoffice/v1/user-shift?user_id={$user->id}&shift_date=".now()->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    });
});