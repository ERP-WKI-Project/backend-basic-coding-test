<?php

use App\Models\User;
use App\DTOs\UserDto;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    $this->service = new UserService();
});

test('it can get all users paginated', function () {
    User::factory()->count(15)->create();

    $result = $this->service->getAll(10);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(15)
        ->and($result->perPage())->toBe(10);
});

test('it can find a user by id', function () {
    $user = User::factory()->create();

    $found = $this->service->findById($user->id);

    expect($found->id)->toBe($user->id)
        ->and($found->name)->toBe($user->name);
});

test('it throws exception if user not found by id', function () {
    $this->service->findById(999);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('it can create a user with a hashed password', function () {
    // Mock
    Hash::shouldReceive('make')
        ->andReturn('hashed_password_string');
    Hash::shouldReceive('isHashed')->andReturn(false);
    Hash::shouldReceive('check')->andReturn(true);
    Hash::shouldReceive('needsRehash')->andReturn(false);

    $dto = new UserDto(
        employee_number: '898989',
        name: 'Sai',
        email: 'sai@mail.com',
        password: 'secret123'
    );

    // Act
    $user = $this->service->create($dto);

    // Assert
    expect($user->employee_number)->toBe('898989')
        ->and($user->password)->toBe('hashed_password_string');

    $this->assertDatabaseHas('users', [
        'email' => 'sai@mail.com',
        'employee_number' => '898989'
    ]);
});

test('it can update user without changing password if password is null in dto', function () {
    // Create a user with a known password
    $user = User::factory()->create([
        'password' => 'secret123' // Laravel hashes this automatically
    ]);

    $dto = new UserDto(
        employee_number: '898989',
        name: 'Sai',
        email: 'sai@mail.com',
        password: null // Password not provided
    );

    // Act
    $updatedUser = $this->service->update($user->id, $dto);

    // Assert
    expect($updatedUser->name)->toBe('Sai');

    // Instead of checking the string, check if the password still works
    expect(Hash::check('secret123', $updatedUser->password))->toBeTrue();
});

test('it can delete a user', function () {
    $user = User::factory()->create();

    $this->service->delete($user->id);

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});
