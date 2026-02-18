<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

describe('BackOffice Auth', function () {

    test('login with valid credentials returns token', function () {
        $response = $this->postJson('/api/backoffice/v1/auth/login', [
            'employee_number' => '000001',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'employee_number',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    });

    test('login with invalid employee number returns 401', function () {
        $response = $this->postJson('/api/backoffice/v1/auth/login', [
            'employee_number' => '999999',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials'
            ]);
    });

    test('login with invalid password returns 401', function () {
        $response = $this->postJson('/api/backoffice/v1/auth/login', [
            'employee_number' => '000001',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials'
            ]);
    });

    test('login with missing employee number returns validation error', function () {
        $response = $this->postJson('/api/backoffice/v1/auth/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number']);
    });

    test('login with missing password returns validation error', function () {
        $response = $this->postJson('/api/backoffice/v1/auth/login', [
            'employee_number' => '000001',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    test('logout with valid token revokes access', function () {
        $user = User::where('employee_number', '000001')->first();

        Sanctum::actingAs($user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

        $response = $this->postJson('/api/backoffice/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully'
            ]);
    });

    test('logout without authentication returns 401', function () {
        $response = $this->postJson('/api/backoffice/v1/auth/logout');

        $response->assertStatus(401);
    });

    test('logout with wrong ability returns 403', function () {
        $user = User::where('employee_number', '000001')->first();

        Sanctum::actingAs($user, [\App\Enums\SystemAbility::MACHINE->value]);

        $response = $this->postJson('/api/backoffice/v1/auth/logout');

        $response->assertStatus(403);
    });
});

