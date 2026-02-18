<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    
    // Acting as backoffice user with proper ability
    $backofficeUser = User::where('employee_number', '000001')->first();
    Sanctum::actingAs($backofficeUser, [\App\Enums\SystemAbility::BACKOFFICE->value]);
});

describe('User Management - List Users', function () {
    test('can list all users with pagination', function () {
        $response = $this->getJson('api/backoffice/v1/user');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'employee_number',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ]
            ],
            'links',
            'meta'
        ]);
    });
});

describe('User Management - Create User', function () {
    test('can create user with valid data', function () {
        $userData = [
            'employee_number' => '123456',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!'
        ];

        $response = $this->postJson('api/backoffice/v1/user', $userData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'employee_number',
                'name',
                'email',
                'created_at',
                'updated_at',
            ]
        ]);
        $response->assertJsonFragment([
            'employee_number' => '123456',
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Verify user exists in database
        $this->assertDatabaseHas('users', [
            'employee_number' => '123456',
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    });

    test('fails with validation errors', function ($userData, $expectedErrors) {
        // Create existing user if testing duplicate scenarios
        if (isset($userData['_setup'])) {
            if ($userData['_setup'] === 'duplicate_employee') {
                User::factory()->create(['employee_number' => '999999']);
            } elseif ($userData['_setup'] === 'duplicate_email') {
                User::factory()->create([
                    'employee_number' => '888888',
                    'email' => 'existing@example.com'
                ]);
            }
            unset($userData['_setup']);
        }

        $response = $this->postJson('api/backoffice/v1/user', $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrors);
    })->with([
        'duplicate employee_number' => [
            ['employee_number' => '999999', 'name' => 'Duplicate User', 'password' => 'SecurePass123!', '_setup' => 'duplicate_employee'],
            ['employee_number']
        ],
        'invalid employee_number format' => [
            ['employee_number' => 'ABC123', 'name' => 'Invalid Employee', 'password' => 'SecurePass123!'],
            ['employee_number']
        ],
        'short employee_number' => [
            ['employee_number' => '12345', 'name' => 'Short Employee Number', 'password' => 'SecurePass123!'],
            ['employee_number']
        ],
        'weak password' => [
            ['employee_number' => '111111', 'name' => 'Weak Password User', 'password' => 'weak', 'email' => 'weak@example.com'],
            ['password']
        ],
        'missing required fields' => [
            [],
            ['employee_number', 'name', 'email', 'password']
        ],
        'duplicate email' => [
            ['employee_number' => '777777', 'name' => 'Duplicate Email User', 'email' => 'existing@example.com', 'password' => 'SecurePass123!', '_setup' => 'duplicate_email'],
            ['email']
        ],
        'missing email' => [
            ['employee_number' => '888888', 'name' => 'No Email User', 'password' => 'SecurePass123!'],
            ['email']
        ],
    ]);
});

describe('User Management - Show User', function () {
    test('can show specific user by employee_number', function () {
        $user = User::factory()->create(['employee_number' => '888888']);

        $response = $this->getJson("api/backoffice/v1/user/{$user->employee_number}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'employee_number',
                'name',
                'email',
                'created_at',
                'updated_at',
            ]
        ]);
        $response->assertJsonFragment([
            'employee_number' => '888888',
        ]);
    });

    test('returns 404 for non-existent user', function () {
        $response = $this->getJson('api/backoffice/v1/user/999999');

        $response->assertStatus(404);
    });
});

describe('User Management - Update User', function () {
    test('can update user fields', function ($updateData, $expected) {
        $user = User::factory()->create([
            'employee_number' => '555555',
            'name' => 'Old Name',
            'email' => 'old@example.com'
        ]);

        $response = $this->putJson("api/backoffice/v1/user/{$user->employee_number}", $updateData);

        $response->assertStatus(200);
        $response->assertJsonFragment($expected);

        // Verify in database
        $this->assertDatabaseHas('users', array_merge(
            ['employee_number' => '555555'],
            $expected
        ));
    })->with([
        'update name' => [
            ['name' => 'New Name'],
            ['name' => 'New Name']
        ],
        'update email' => [
            ['email' => 'newemail@example.com'],
            ['email' => 'newemail@example.com']
        ],
        'update multiple fields' => [
            ['name' => 'Updated Name', 'email' => 'updated@example.com'],
            ['name' => 'Updated Name', 'email' => 'updated@example.com']
        ],
    ]);

    test('can update user password', function () {
        $user = User::factory()->create(['employee_number' => '333333']);
        $oldPassword = $user->password;

        $response = $this->putJson("api/backoffice/v1/user/{$user->employee_number}", [
            'password' => 'NewSecurePass123!'
        ]);

        $response->assertStatus(200);

        $user->refresh();
        // Password should be different after update
        expect($user->password)->not->toBe($oldPassword);
    });

    test('fails to update with duplicate employee_number', function () {
        $user1 = User::factory()->create(['employee_number' => '100001']);
        $user2 = User::factory()->create(['employee_number' => '100002']);

        $response = $this->putJson("api/backoffice/v1/user/{$user2->employee_number}", [
            'employee_number' => '100001' // Already exists
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['employee_number']);
    });

    test('can update to same employee_number', function () {
        $user = User::factory()->create(['employee_number' => '100003']);

        $response = $this->putJson("api/backoffice/v1/user/{$user->employee_number}", [
            'employee_number' => '100003', // Same as current
            'name' => 'Updated Name'
        ]);

        $response->assertStatus(200);
    });
});

describe('User Management - Delete User', function () {
    test('can delete user without active shifts', function () {
        $user = User::factory()->create(['employee_number' => '666666']);
        $userId = $user->id;

        $response = $this->deleteJson("api/backoffice/v1/user/{$user->employee_number}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['message']);

        // Verify soft delete
        $this->assertSoftDeleted('users', [
            'id' => $userId,
        ]);
    });

    test('fails to delete user with active shifts', function () {
        $user = User::factory()->create(['employee_number' => '777777']);
        
        // Create an active shift for today or future
        $shift = \App\Models\Shift::first();
        \App\Models\UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'machine_code' => 'TEST-MACHINE-001',
            'shift_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->deleteJson("api/backoffice/v1/user/{$user->employee_number}");

        $response->assertStatus(500); // Exception thrown by service
        
        // User should still exist
        $this->assertDatabaseHas('users', [
            'employee_number' => '777777',
            'deleted_at' => null,
        ]);
    });
});

describe('User Management - Authorization', function () {
    test('unauthorized user cannot access user endpoints', function () {
        // Create request without authentication
        Sanctum::actingAs(User::first(), []); // No abilities

        $response = $this->getJson('api/backoffice/v1/user');

        $response->assertStatus(403); // Forbidden due to missing ability
    });

    test('guest cannot access user endpoints', function () {
        // Create request without any authentication (override beforeEach)
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('api/backoffice/v1/user');

        $response->assertStatus(401); // Unauthenticated
    });
});
