<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('login', function () {
    test('berhasil login dan mengembalikan access_token', function () {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/backoffice/v1/auth/login', [
            'employee_number' => $user->employee_number,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['access_token', 'token_type'],
                'errors',
            ])
            ->assertJsonFragment([
                'success' => true,
                'token_type' => 'Bearer',
            ]);
    });

    test('mengembalikan status 401 ketika password salah', function () {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/backoffice/v1/auth/login', [
            'employee_number' => $user->employee_number,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['success' => false]);
    });

    test('mengembalikan status 404 ketika user tidak ditemukan', function () {
        $response = $this->postJson('/api/backoffice/v1/auth/login', [
            'employee_number' => '999999',
            'password' => 'password123',
        ]);

        $response->assertStatus(404)
            ->assertJsonFragment(['success' => false]);
    });

    test('mengembalikan error validasi 422 ketika request body kosong', function () {
        $response = $this->postJson('/api/backoffice/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number', 'password']);
    });

    test('mengembalikan error validasi 422 ketika employee_number format salah', function () {
        $response = $this->postJson('/api/backoffice/v1/auth/login', [
            'employee_number' => '12345',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number']);
    });
});

describe('logout', function () {
    test('berhasil logout dan mengembalikan status 200', function () {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        // Login dulu untuk mendapatkan token
        $loginResponse = $this->postJson('/api/backoffice/v1/auth/login', [
            'employee_number' => $user->employee_number,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.access_token');

        // Logout menggunakan token
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/backoffice/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);
    });

    test('mengembalikan status 401 ketika logout tanpa token', function () {
        $response = $this->postJson('/api/backoffice/v1/auth/logout');

        $response->assertStatus(401);
    });
});
