<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'employee_number' => '000001',
        'password' => bcrypt('password'),
    ]);
});

test('unauthenticated user cannot access user endpoints', function () {
    $this->getJson(route('api.backoffice.v1.user.index'))->assertStatus(401);
    $this->postJson(route('api.backoffice.v1.user.store'), [])->assertStatus(401);

    $targetUser = User::factory()->create();
    $this->putJson(route('api.backoffice.v1.user.update', $targetUser), [])->assertStatus(401);
    $this->deleteJson(route('api.backoffice.v1.user.destroy', $targetUser))->assertStatus(401);
});

test('admin can list users with pagination', function () {
    User::factory()->count(15)->create();

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->getJson(route('api.backoffice.v1.user.index'));

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonCount(10, 'data'); // Default pagination is 10
});

test('admin can create a user', function () {
    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $data = [
        'employee_number' => '123456',
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'secret123',
    ];

    $response = $this->postJson(route('api.backoffice.v1.user.store'), $data);

    $response->assertStatus(201)
        ->assertJsonFragment(['employee_number' => '123456']);

    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);

    $user = User::where('email', 'newuser@example.com')->first();
    expect(Hash::check('secret123', $user->password))->toBeTrue();
});

test('admin cannot create user with invalid data', function () {
    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    // Required fields check
    $this->postJson(route('api.backoffice.v1.user.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['employee_number', 'name', 'password']);

    // Format checks
    $this->postJson(route('api.backoffice.v1.user.store'), [
        'employee_number' => '12345', // Too short (needs 6 digits)
        'name' => 'Test User',
        'email' => 'not-an-email',
        'password' => 'short', // Too short (min 6)
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['employee_number', 'email', 'password']);

    // Numeric check for employee_number
    $this->postJson(route('api.backoffice.v1.user.store'), [
        'employee_number' => 'ABCDEF', // Not numeric
        'name' => 'Test User',
        'password' => 'secret123',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['employee_number']);
});

test('admin cannot create duplicate user email or employee number', function () {
    User::factory()->create(['email' => 'duplicate@example.com', 'employee_number' => '111111']);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $this->postJson(route('api.backoffice.v1.user.store'), [
        'employee_number' => '111111',
        'name' => 'Fail User',
        'email' => 'new@example.com',
        'password' => 'secret123',
    ])->assertStatus(422)->assertJsonValidationErrors(['employee_number']);

    $this->postJson(route('api.backoffice.v1.user.store'), [
        'employee_number' => '222222',
        'name' => 'Fail User',
        'email' => 'duplicate@example.com',
        'password' => 'secret123',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('admin can update a user', function () {
    $targetUser = User::factory()->create(['password' => bcrypt('oldpassword')]);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $data = [
        'employee_number' => $targetUser->employee_number,
        'name' => 'Updated User Name',
        'email' => $targetUser->email,
        'password' => 'newsecret',
    ];

    $response = $this->putJson(route('api.backoffice.v1.user.update', $targetUser), $data);

    $response->assertStatus(200)
        ->assertJsonFragment(['name' => 'Updated User Name']);

    $targetUser->refresh();
    expect($targetUser->name)->toBe('Updated User Name');
    expect(Hash::check('newsecret', $targetUser->password))->toBeTrue();
});

test('admin can update user without changing password', function () {
    $targetUser = User::factory()->create(['password' => bcrypt('keep_this_password')]);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $data = [
        'employee_number' => $targetUser->employee_number,
        'name' => 'Updated Name Only',
        'email' => $targetUser->email,
        // password not sent or can be empty string in some implementations, but here DTO usually handles it
        // The DTO says: password: $request->filled('password') ? ... : null
    ];

    $this->putJson(route('api.backoffice.v1.user.update', $targetUser), $data)
        ->assertStatus(200);

    $targetUser->refresh();
    expect($targetUser->name)->toBe('Updated Name Only');
    expect(Hash::check('keep_this_password', $targetUser->password))->toBeTrue();
});

test('admin can update user ignoring unique checks for self', function () {
    $targetUser = User::factory()->create(['email' => 'initial@example.com', 'employee_number' => '121212']);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    // Send same email/employee_number
    $this->putJson(route('api.backoffice.v1.user.update', $targetUser), [
        'employee_number' => '121212',
        'name' => 'Still Me',
        'email' => 'initial@example.com',
    ])->assertStatus(200);
});

test('admin cannot update user to use duplicate email or employee number', function () {
    $otherUser = User::factory()->create(['email' => 'taken@example.com', 'employee_number' => '999999']);
    $targetUser = User::factory()->create(['email' => 'me@example.com', 'employee_number' => '888888']);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    // Try to take other user's employee number
    $this->putJson(route('api.backoffice.v1.user.update', $targetUser), [
        'employee_number' => '999999',
        'name' => 'Thief',
        'email' => 'me@example.com',
    ])->assertStatus(422)->assertJsonValidationErrors(['employee_number']);

    // Try to take other user's email
    $this->putJson(route('api.backoffice.v1.user.update', $targetUser), [
        'employee_number' => '888888',
        'name' => 'Thief',
        'email' => 'taken@example.com',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});


test('admin can delete a user', function () {
    $targetUser = User::factory()->create();

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->deleteJson(route('api.backoffice.v1.user.destroy', $targetUser));

    $response->assertStatus(200); // Controller returns 200 with success message usually based on code 

    $this->assertSoftDeleted('users', ['id' => $targetUser->id]);
});

test('admin can search users', function () {
    User::factory()->create(['name' => 'Searchable User', 'employee_number' => 'SRC001']);
    User::factory()->create(['name' => 'Hidden User', 'employee_number' => 'HID001']);

    Sanctum::actingAs($this->user, [\App\Enums\SystemAbility::BACKOFFICE->value]);

    $response = $this->getJson(route('api.backoffice.v1.user.index', ['search' => 'Searchable']));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Searchable User'])
        ->assertJsonMissing(['name' => 'Hidden User']);
});
