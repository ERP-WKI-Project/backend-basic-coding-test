<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'employee_number' => '000001',
        'password' => bcrypt('password'),
    ]);
});

test('admin can list users', function () {
    User::factory()->count(3)->create();

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->getJson(route('api.backoffice.v1.user.index'));

    $response->assertStatus(200)
             ->assertJsonStructure(['data', 'links', 'meta'])
             ->assertJsonCount(3 + 1, 'data'); // +1 because current auth user is also created
});

test('admin can create a user', function () {
    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $data = [
        'employee_number' => 'EMP002', // Max 6 chars
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'secret123',
    ];

    $response = $this->postJson(route('api.backoffice.v1.user.store'), $data);

    $response->assertStatus(201)
             ->assertJsonFragment(['employee_number' => 'EMP002']);

    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});

test('admin cannot create duplicate user email', function () {
    User::factory()->create(['email' => 'duplicate@example.com']);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->postJson(route('api.backoffice.v1.user.store'), [
        'employee_number' => 'EMP003',
        'name' => 'Fail User',
        'email' => 'duplicate@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

test('admin can update a user', function () {
    $targetUser = User::factory()->create();

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $data = [
        'employee_number' => $targetUser->employee_number,
        'name' => 'Updated User Name',
        'email' => $targetUser->email,
        'password' => 'newsecret',
    ];

    // Using route params because route key is employee_number
    $response = $this->putJson(route('api.backoffice.v1.user.update', $targetUser), $data);

    $response->assertStatus(200)
             ->assertJsonFragment(['name' => 'Updated User Name']);

    $this->assertDatabaseHas('users', ['id' => $targetUser->id, 'name' => 'Updated User Name']);
});

test('admin can delete a user', function () {
    $targetUser = User::factory()->create();

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->deleteJson(route('api.backoffice.v1.user.destroy', $targetUser));

    $response->assertStatus(204);

    $this->assertSoftDeleted('users', ['id' => $targetUser->id]);
});

test('admin can search users', function () {
    User::factory()->create(['name' => 'Searchable User', 'employee_number' => 'SRC001']);
    
    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->getJson(route('api.backoffice.v1.user.index', ['search' => 'Searchable']));

    $response->assertStatus(200)
             ->assertJsonCount(1, 'data')
             ->assertJsonFragment(['name' => 'Searchable User']);
});
