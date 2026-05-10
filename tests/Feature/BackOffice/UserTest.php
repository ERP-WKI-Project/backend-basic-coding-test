<?php

namespace Tests\Feature\BackOffice;

use App\Enums\SystemAbility;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->adminUser = User::factory()->create([
        'employee_number' => '000001',
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => bcrypt('password123'),
    ]);
});

describe('User Index', function () {
    test('user_index_returns_paginated_users', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        User::factory()->count(15)->create();

        $response = getJson('/api/backoffice/v1/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 16);
    });

    test('user_index_with_limit', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        User::factory()->count(10)->create();

        $response = getJson('/api/backoffice/v1/user?limit=5');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 11);
    });

    test('user_index_with_search_on_name', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $response = getJson('/api/backoffice/v1/user?search=John');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    });

    test('user_index_with_search_on_email', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        User::factory()->create(['email' => 'john@example.com']);
        User::factory()->create(['email' => 'jane@example.com']);

        $response = getJson('/api/backoffice/v1/user?search=john@example');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    });

    test('user_index_with_search_on_employee_number', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        User::factory()->create(['employee_number' => '123456']);

        $response = getJson('/api/backoffice/v1/user?search=123456');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    });

    test('user_index_no_results', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = getJson('/api/backoffice/v1/user?search=NONEXISTENT');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 0);
    });
});

describe('User Store', function () {
    test('user_store_success', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = postJson('/api/backoffice/v1/user', [
            'employee_number' => '999999',
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'employee_number' => '999999',
                'name' => 'New User',
                'email' => 'newuser@example.com',
            ]);

        $this->assertDatabaseHas('users', [
            'employee_number' => '999999',
            'name' => 'New User',
        ]);
    });

    test('user_store_validation_failed', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = postJson('/api/backoffice/v1/user', [
            'employee_number' => '',
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number', 'name', 'email', 'password']);
    });

    test('user_store_duplicate_employee_number', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        User::factory()->create(['employee_number' => '888888']);

        $response = postJson('/api/backoffice/v1/user', [
            'employee_number' => '888888',
            'name' => 'Duplicate',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number']);
    });

    test('user_store_duplicate_email', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        User::factory()->create(['email' => 'taken@example.com']);

        $response = postJson('/api/backoffice/v1/user', [
            'employee_number' => '777777',
            'name' => 'Duplicate Email',
            'email' => 'taken@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('user_store_employee_number_must_be_6_chars', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = postJson('/api/backoffice/v1/user', [
            'employee_number' => '12345',
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number']);
    });

    test('user_store_password_min_8_chars', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = postJson('/api/backoffice/v1/user', [
            'employee_number' => '666666',
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });
});

describe('User Show', function () {
    test('user_show_success', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user = User::factory()->create();

        $response = getJson("/api/backoffice/v1/user/{$user->employee_number}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $user->id,
                'employee_number' => $user->employee_number,
                'name' => $user->name,
            ]);
    });

    test('user_show_not_found', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = getJson('/api/backoffice/v1/user/NOTFOUND');

        $response->assertStatus(404);
    });
});

describe('User Update', function () {
    test('user_update_success', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user = User::factory()->create();

        $response = putJson("/api/backoffice/v1/user/{$user->employee_number}", [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    });

    test('user_update_with_password', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user = User::factory()->create();

        $response = putJson("/api/backoffice/v1/user/{$user->employee_number}", [
            'password' => 'newpassword123',
        ]);

        $response->assertStatus(200);
    });

    test('user_update_not_found', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = putJson('/api/backoffice/v1/user/NOTFOUND', [
            'name' => 'Updated',
        ]);

        $response->assertStatus(404);
    });

    test('user_update_duplicate_employee_number', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user1 = User::factory()->create(['employee_number' => '111111']);
        $user2 = User::factory()->create(['employee_number' => '222222']);

        $response = putJson("/api/backoffice/v1/user/{$user2->employee_number}", [
            'employee_number' => $user1->employee_number,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number']);
    });

    test('user_update_duplicate_email', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $response = putJson("/api/backoffice/v1/user/{$user2->employee_number}", [
            'email' => $user1->email,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('user_update_password_min_8_chars', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user = User::factory()->create();

        $response = putJson("/api/backoffice/v1/user/{$user->employee_number}", [
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    test('user_update_self_employee_number_allowed', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = putJson("/api/backoffice/v1/user/{$this->adminUser->employee_number}", [
            'employee_number' => $this->adminUser->employee_number,
            'name' => 'Updated Self',
            'email' => $this->adminUser->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Self']);
    });

    test('user_update_self_email_allowed', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = putJson("/api/backoffice/v1/user/{$this->adminUser->employee_number}", [
            'email' => $this->adminUser->email,
            'name' => 'Updated Self Email',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Self Email']);
    });
});

describe('User Delete', function () {
    test('user_delete_success', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user = User::factory()->create();

        $response = deleteJson("/api/backoffice/v1/user/{$user->employee_number}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'User berhasil dihapus.']);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    });

    test('user_delete_not_found', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = deleteJson('/api/backoffice/v1/user/NOTFOUND');

        $response->assertStatus(404);
    });
});

describe('User Restore', function () {
    test('user_restore_success', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user = User::factory()->create();
        $user->delete();

        $response = postJson("/api/backoffice/v1/user/{$user->id}/restore");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'User berhasil dipulihkan.']);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    });

    test('user_restore_not_found', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = postJson('/api/backoffice/v1/user/99999999/restore');

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'User tidak ditemukan.']);
    });
});

describe('Auth & Authorization', function () {
    test('user_unauthenticated_redirects', function () {
        $response = getJson('/api/backoffice/v1/user');

        $this->assertContains($response->getStatusCode(), [401, 403, 500]);
    });

    test('user_wrong_ability', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::MACHINE->value]);

        $response = getJson('/api/backoffice/v1/user');

        $response->assertStatus(403);
    });
});
