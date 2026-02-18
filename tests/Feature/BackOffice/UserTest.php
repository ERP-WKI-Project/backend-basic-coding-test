<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    // Authenticate as backoffice user
    $user = User::where('employee_number', '000001')->first();
    Sanctum::actingAs($user, [\App\Enums\SystemAbility::BACKOFFICE->value]);
});

describe('BackOffice User Management', function () {

    test('index returns paginated list of users', function () {
        $response = $this->getJson('/api/backoffice/v1/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'employee_number',
                        'name',
                        'email',
                    ]
                ],
                'links',
                'meta'
            ]);
    });

    test('index with search query filters users', function () {
        $response = $this->getJson('/api/backoffice/v1/user?q=000001');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'employee_number',
                        'name',
                        'email',
                    ]
                ]
            ]);
    });

    test('index with limit parameter returns correct number of items', function () {
        $response = $this->getJson('/api/backoffice/v1/user?limit=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['per_page']
            ]);

        expect($response->json('meta.per_page'))->toBe(5);
    });

    test('store creates new user with valid data', function () {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ];

        $response = $this->postJson('/api/backoffice/v1/user', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'employee_number',
                    'name',
                    'email',
                ]
            ])
            ->assertJsonFragment([
                'name' => 'New User',
                'email' => 'newuser@example.com',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);
    });

    test('store with missing required fields returns validation error', function () {
        $response = $this->postJson('/api/backoffice/v1/user', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    test('store with invalid email returns validation error', function () {
        $response = $this->postJson('/api/backoffice/v1/user', [
            'name' => 'Test User',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('store with duplicate email returns validation error', function () {
        // Create a user with email first
        $this->postJson('/api/backoffice/v1/user', [
            'name' => 'First User',
            'email' => 'duplicate@example.com',
        ]);

        // Try to create another user with same email
        $response = $this->postJson('/api/backoffice/v1/user', [
            'name' => 'Second User',
            'email' => 'duplicate@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('show returns user details by employee number', function () {
        $user = User::where('employee_number', '000001')->first();

        $response = $this->getJson("/api/backoffice/v1/user/{$user->employee_number}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'employee_number',
                    'name',
                    'email',
                ]
            ])
            ->assertJsonFragment([
                'employee_number' => $user->employee_number,
            ]);
    });

    test('show with non-existent employee number returns 404', function () {
        $response = $this->getJson('/api/backoffice/v1/user/999999');

        $response->assertStatus(404);
    });

    test('update modifies user with valid data', function () {
        $user = User::where('employee_number', '000001')->first();

        $response = $this->putJson("/api/backoffice/v1/user/{$user->employee_number}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Name',
            ]);

        $this->assertDatabaseHas('users', [
            'employee_number' => $user->employee_number,
            'name' => 'Updated Name',
        ]);
    });

    test('update with invalid email returns validation error', function () {
        $user = User::where('employee_number', '000001')->first();

        $response = $this->putJson("/api/backoffice/v1/user/{$user->employee_number}", [
            'name' => 'Test',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('update with duplicate email returns validation error', function () {
        // Create first user with email
        $this->postJson('/api/backoffice/v1/user', [
            'name' => 'First User',
            'email' => 'first@example.com',
        ]);

        // Create second user with different email
        $response = $this->postJson('/api/backoffice/v1/user', [
            'name' => 'Second User',
            'email' => 'second@example.com',
        ]);
        $user2EmployeeNumber = $response->json('data.employee_number');

        // Try to update second user with first user's email
        $response = $this->putJson("/api/backoffice/v1/user/{$user2EmployeeNumber}", [
            'name' => 'Second User Updated',
            'email' => 'first@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('destroy soft deletes user', function () {
        $user = User::where('employee_number', '000001')->first();

        $response = $this->deleteJson("/api/backoffice/v1/user/{$user->employee_number}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User deleted successfully'
            ]);

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    });

    test('destroy with non-existent employee number returns 404', function () {
        $response = $this->deleteJson('/api/backoffice/v1/user/999999');

        $response->assertStatus(404);
    });

    test('unauthenticated request to user endpoints returns 401', function () {
        // Reset authentication by creating a new test instance without Sanctum
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/backoffice/v1/user');

        $response->assertStatus(401);
    });

    test('request with wrong ability returns 403', function () {
        $user = User::where('employee_number', '000001')->first();
        Sanctum::actingAs($user, [\App\Enums\SystemAbility::MACHINE->value]);

        $response = $this->getJson('/api/backoffice/v1/user');

        $response->assertStatus(403);
    });
});

