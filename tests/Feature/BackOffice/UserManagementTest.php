<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();

    Sanctum::actingAs(
        $this->user,
        [\App\Enums\SystemAbility::BACKOFFICE->value]
    );
});

describe('index', function () {
    test('mengembalikan daftar seluruh user beserta format paginasi yang valid', function () {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/backoffice/v1/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['employee_number', 'name', 'email', 'created_at', 'updated_at'],
                ],
                'errors',
            ])
            ->assertJsonFragment(['success' => true]);
    });

    test('mengembalikan daftar user berdasarkan name beserta format paginasi yang valid', function () {
        User::factory()->create(['name' => 'Budi Santoso']);
        User::factory()->create(['name' => 'Andi Wijaya']);
        User::factory()->create(['name' => 'Budi Pratama']);

        $response = $this->getJson('/api/backoffice/v1/users?search=Budi');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data)->toHaveCount(2);
        expect(collect($data)->pluck('name')->toArray())
            ->each(fn ($name) => $name->toContain('Budi'));
    });

    test('mengembalikan daftar user berdasarkan employee_number beserta format paginasi yang valid', function () {
        User::factory()->create(['employee_number' => '100001']);
        User::factory()->create(['employee_number' => '100002']);
        User::factory()->create(['employee_number' => '200001']);

        $response = $this->getJson('/api/backoffice/v1/users?search=1000');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data)->toHaveCount(2);
    });

    test('mengembalikan data kosong jika search tidak cocok', function () {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/backoffice/v1/users?search=tidakada999');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data)->toHaveCount(0);
    });
});

describe('store', function () {
    test('berhasil membuat user baru dan mengembalikan status 201', function () {
        $payload = [
            'employee_number' => '100001',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/backoffice/v1/users', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['employee_number', 'name', 'email', 'created_at', 'updated_at'],
                'errors',
            ])
            ->assertJsonFragment([
                'success' => true,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    });

    test('berhasil membuat user baru tanpa menyertakan email dan mengembalikan status 201', function () {
        $response = $this->postJson('/api/backoffice/v1/users', [
            'employee_number' => '100001',
            'name' => 'No Email User',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'success' => true,
                'name' => 'No Email User',
                'email' => null,
            ]);

        $this->assertDatabaseHas('users', [
            'employee_number' => '100001',
            'name' => 'No Email User',
            'email' => null,
        ]);
    });

    test('berhasil melakukan hashing pada password ketika membuat user baru', function () {
        $payload = [
            'employee_number' => '100006',
            'name' => 'Hash Check User',
            'password' => 'secretPassword123',
        ];

        $this->postJson('/api/backoffice/v1/users', $payload)->assertStatus(201);

        $user = User::where('employee_number', '100006')->first();
        expect(Hash::check('secretPassword123', $user->password))->toBeTrue();
    });

    test('mengembalikan error validasi 422 ketika request body kosong', function () {
        $response = $this->postJson('/api/backoffice/v1/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number', 'name', 'password']);
    });

    test('mengembalikan error validasi 422 ketika email sudah terdaftar', function () {
        $existing = User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/backoffice/v1/users', [
            'employee_number' => '100002',
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('mengembalikan error validasi 422 ketika employee_number sudah terdaftar', function () {
        $existing = User::factory()->create(['employee_number' => '100001']);

        $response = $this->postJson('/api/backoffice/v1/users', [
            'employee_number' => '100001',
            'name' => 'Another User',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number']);
    });

    test('mengembalikan error validasi 422 ketika employee_number tidak valid', function () {
        $response = $this->postJson('/api/backoffice/v1/users', [
            'employee_number' => '12345',
            'name' => 'Short NIP',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number']);
    });

    test('mengembalikan error validasi 422 ketika panjang password kurang dari 8 karakter', function () {
        $response = $this->postJson('/api/backoffice/v1/users', [
            'employee_number' => '100005',
            'name' => 'Weak Password User',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });
});

describe('show', function () {
    test('mengembalikan detail spesifik user berdasarkan employee_number dan mengembalikan status 200', function () {
        $target = User::factory()->create();

        $response = $this->getJson("/api/backoffice/v1/users/{$target->employee_number}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['employee_number', 'name', 'email', 'created_at', 'updated_at'],
                'errors',
            ])
            ->assertJsonFragment([
                'success' => true,
                'employee_number' => $target->employee_number,
                'name' => $target->name,
            ]);
    });

    test('mengembalikan status 404 jika user tidak ditemukan', function () {
        $response = $this->getJson('/api/backoffice/v1/users/999999');

        $response->assertStatus(404);
    });
});

describe('update', function () {
    test('berhasil mengupdate user seluruh field dan mengembalikan status 200', function () {
        $target = User::factory()->create();

        $response = $this->putJson("/api/backoffice/v1/users/{$target->employee_number}", [
            'employee_number' => '999999',
            'name' => 'Updated Name',
            'email' => 'updatedemail@gmail.com',
            'password' => 'Updated Password',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'employee_number' => '999999',
                'name' => 'Updated Name',
                'email' => 'updatedemail@gmail.com',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'employee_number' => '999999',
            'name' => 'Updated Name',
            'email' => 'updatedemail@gmail.com',
        ]);
    });

    test('berhasil mengupdate user sebagian field tanpa mengubah field lain dan mengembalikan status 200', function () {
        $target = User::factory()->create([
            'employee_number' => '100001',
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $response = $this->putJson("/api/backoffice/v1/users/{$target->employee_number}", [
            'name' => 'Budi Santoso',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Budi Santoso',
                'email' => 'original@example.com',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Budi Santoso',
            'email' => 'original@example.com',
            'employee_number' => '100001',
        ]);
    });

    test('berhasil mengupdate user dengan email lama milik user itu sendiri dan mengembalikan status 200', function () {
        $target = User::factory()->create();

        $response = $this->putJson("/api/backoffice/v1/users/{$target->employee_number}", [
            'email' => $target->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'email' => $target->email,
            ]);
    });

    test('berhasil mengupdate email menjadi null dan mengembalikan status 200', function () {
        $target = User::factory()->create(['email' => 'old@example.com']);

        $response = $this->putJson("/api/backoffice/v1/users/{$target->employee_number}", [
            'email' => null,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'email' => null,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'email' => null,
        ]);
    });

    test('berhasil mengupdate user dengan employee_number milik user itu sendiri dan mengembalikan status 200', function () {
        $target = User::factory()->create(['employee_number' => '100001']);

        $response = $this->putJson("/api/backoffice/v1/users/{$target->employee_number}", [
            'employee_number' => $target->employee_number,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'employee_number' => $target->employee_number,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'employee_number' => $target->employee_number,
        ]);
    });

    test('berhasil mengupdate password dan mengembalikan status 200', function () {
        $target = User::factory()->create();

        $response = $this->putJson("/api/backoffice/v1/users/{$target->employee_number}", [
            'password' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        $target->refresh();
        expect(Hash::check('newpassword123', $target->password))->toBeTrue();
    });

    test('mengembalikan error validasi 422 ketika employee_number sudah terdaftar', function () {
        $existing = User::factory()->create(['employee_number' => '100001']);
        $target = User::factory()->create(['employee_number' => '100002']);

        $response = $this->putJson("/api/backoffice/v1/users/{$target->employee_number}", [
            'employee_number' => '100001',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number']);
    });

    test('mengembalikan error validasi 422 ketika email sudah terdaftar', function () {
        $existing = User::factory()->create(['email' => 'taken@example.com']);
        $target = User::factory()->create();

        $response = $this->putJson("/api/backoffice/v1/users/{$target->employee_number}", [
            'email' => 'taken@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('mengembalikan error validasi 422 ketika employee_number format salah', function () {
        $target = User::factory()->create();

        $response = $this->putJson("/api/backoffice/v1/users/{$target->employee_number}", [
            'employee_number' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_number']);
    });

    test('mengembalikan error validasi 422 ketika memperbarui password dengan panjang kurang dari 8 karakter', function () {
        $target = User::factory()->create();

        $response = $this->putJson("/api/backoffice/v1/users/{$target->employee_number}", [
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    test('mengembalikan status 404 jika user tidak ditemukan', function () {
        $response = $this->putJson('/api/backoffice/v1/users/999999', [
            'name' => 'Ghost User',
        ]);

        $response->assertStatus(404);
    });
});

describe('destroy', function () {
    test('berhasil soft delete user dan mengembalikan status 200', function () {
        $target = User::factory()->create();

        $response = $this->deleteJson("/api/backoffice/v1/users/{$target->employee_number}");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data', 'errors'])
            ->assertJsonFragment([
                'success' => true,
                'data' => null,
            ]);

        $this->assertSoftDeleted('users', [
            'id' => $target->id,
        ]);
    });

    test('mengembalikan status 404 jika user tidak ditemukan', function () {
        $response = $this->deleteJson('/api/backoffice/v1/users/999999');

        $response->assertStatus(404);
    });
});

describe('unauthenticated', function () {
    test('mengembalikan status 401 jika user tidak terautentikasi', function () {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/backoffice/v1/users')->assertStatus(401);
        $this->postJson('/api/backoffice/v1/users', [])->assertStatus(401);
        $this->putJson('/api/backoffice/v1/users/999999', [])->assertStatus(401);
        $this->deleteJson('/api/backoffice/v1/users/999999')->assertStatus(401);
    });
});
