<?php

use App\Models\User;
use App\Models\Shift;
use App\Models\UserShift;
use App\Services\UserService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->userService = new UserService();
});

describe('UserService - Create User', function () {
    test('creates user with hashed password', function () {
        $userData = [
            'employee_number' => '123456',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'PlainPassword123!'
        ];

        $user = $this->userService->createUser($userData);

        expect($user)->toBeInstanceOf(User::class);
        expect($user->employee_number)->toBe('123456');
        expect($user->name)->toBe('Test User');
        expect($user->email)->toBe('test@example.com');
        
        // Password should be hashed
        expect(Hash::check('PlainPassword123!', $user->password))->toBeTrue();
        expect($user->password)->not->toBe('PlainPassword123!');
    });

    test('creates user with email', function () {
        $userData = [
            'employee_number' => '654321',
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'SecurePass123!'
        ];

        $user = $this->userService->createUser($userData);

        expect($user->employee_number)->toBe('654321');
        expect($user->email)->toBe('testuser@example.com');
    });

    test('transaction rollback on error', function () {
        // This should trigger an error due to duplicate
        User::factory()->create(['employee_number' => '999999']);

        $userData = [
            'employee_number' => '999999',
            'name' => 'Duplicate User',
            'password' => 'SecurePass123!'
        ];

        expect(fn() => $this->userService->createUser($userData))
            ->toThrow(Exception::class);
    });
});

describe('UserService - Update User', function () {
    test('updates user fields correctly', function ($initialData, $updateData, $expectations) {
        $user = User::factory()->create($initialData);
        $originalPassword = $user->password;

        $updatedUser = $this->userService->updateUser($user, $updateData);

        // Check expected values
        foreach ($expectations as $field => $expectedValue) {
            if ($field === 'password_changed') {
                if ($expectedValue === true) {
                    expect($updatedUser->password)->not->toBe($originalPassword);
                } else {
                    expect($updatedUser->password)->toBe($originalPassword);
                }
            } else {
                expect($updatedUser->$field)->toBe($expectedValue);
            }
        }

        // Verify password hashing if password was updated
        if (isset($updateData['password']) && !empty($updateData['password'])) {
            expect(Hash::check($updateData['password'], $updatedUser->password))->toBeTrue();
        }
    })->with([
        'update name' => [
            ['employee_number' => '111111', 'name' => 'Old Name'],
            ['name' => 'New Name'],
            ['name' => 'New Name', 'employee_number' => '111111', 'password_changed' => false]
        ],
        'update and hash password' => [
            ['employee_number' => '222222'],
            ['password' => 'NewPassword123!'],
            ['password_changed' => true]
        ],
        'no password update when not provided' => [
            ['employee_number' => '333333'],
            ['name' => 'Updated Name'],
            ['name' => 'Updated Name', 'password_changed' => false]
        ],
        'remove empty password' => [
            ['employee_number' => '444444'],
            ['name' => 'Updated Name', 'password' => ''],
            ['name' => 'Updated Name', 'password_changed' => false]
        ],
        'update multiple fields' => [
            ['employee_number' => '555555', 'name' => 'Old Name', 'email' => 'old@example.com'],
            ['name' => 'New Name', 'email' => 'new@example.com'],
            ['name' => 'New Name', 'email' => 'new@example.com', 'password_changed' => false]
        ],
    ]);
});

describe('UserService - Delete User', function () {
    test('handles user deletion based on shift status', function ($employeeNumber, $shiftDate, $shouldSucceed) {
        $user = User::factory()->create(['employee_number' => $employeeNumber]);
        
        // Create shift if date provided
        if ($shiftDate !== null) {
            $shift = Shift::first();
            UserShift::create([
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'machine_code' => 'TEST-MACHINE-001',
                'shift_date' => $shiftDate,
            ]);
        }

        if ($shouldSucceed) {
            $result = $this->userService->deleteUser($user);
            expect($result)->toBeTrue();
            $this->assertSoftDeleted('users', ['id' => $user->id]);
        } else {
            expect(fn() => $this->userService->deleteUser($user))
                ->toThrow(Exception::class, 'Tidak dapat menghapus user yang memiliki shift aktif');
            
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'deleted_at' => null
            ]);
        }
    })->with([
        'no shifts' => ['666666', null, true],
        'today shift (active)' => ['777777', now()->format('Y-m-d'), false],
        'past shift' => ['888888', now()->subDay()->format('Y-m-d'), true],
        'future shift (active)' => ['999999', now()->addDay()->format('Y-m-d'), false],
    ]);
});

describe('UserService - Restore User', function () {
    test('restores soft deleted user', function () {
        $user = User::factory()->create(['employee_number' => '100001']);
        $user->delete(); // Soft delete

        // Verify soft deleted
        $this->assertSoftDeleted('users', ['employee_number' => '100001']);

        // Restore
        $restoredUser = $this->userService->restoreUser('100001');

        expect($restoredUser->employee_number)->toBe('100001');
        expect($restoredUser->deleted_at)->toBeNull();
        
        $this->assertDatabaseHas('users', [
            'employee_number' => '100001',
            'deleted_at' => null
        ]);
    });

    test('throws exception when restoring non-existent user', function () {
        expect(fn() => $this->userService->restoreUser('999999'))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });
});

describe('UserService - Business Logic Validation', function () {
    test('transaction ensures data consistency on create', function () {
        $userData = [
            'employee_number' => '200001',
            'name' => 'Transaction Test',
            'email' => 'transaction@example.com',
            'password' => 'SecurePass123!'
        ];

        $user = $this->userService->createUser($userData);

        // Verify all data saved correctly
        $this->assertDatabaseHas('users', [
            'employee_number' => '200001',
            'name' => 'Transaction Test',
            'email' => 'transaction@example.com'
        ]);
        
        expect($user->wasRecentlyCreated)->toBeTrue();
    });

    test('transaction ensures data consistency on update', function () {
        $user = User::factory()->create(['employee_number' => '200002']);

        $updatedUser = $this->userService->updateUser($user, [
            'name' => 'Updated in Transaction',
            'email' => 'transaction@example.com'
        ]);

        // Verify all updates applied
        $this->assertDatabaseHas('users', [
            'employee_number' => '200002',
            'name' => 'Updated in Transaction',
            'email' => 'transaction@example.com'
        ]);
    });
});
