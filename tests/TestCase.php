<?php

namespace Tests;

use App\Enums\SystemAbility;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    /**
     * Authenticate as BackOffice admin
     */
    protected function actingAsBackOffice(?User $user = null): User
    {
        $user ??= User::factory()->create();
        Sanctum::actingAs($user, [SystemAbility::BACKOFFICE->value]);

        return $user;
    }

    /**
     * Authenticate as Machine terminal user
     */
    protected function actingAsMachine(?User $user = null): User
    {
        $user ??= User::factory()->create();
        Sanctum::actingAs($user, [SystemAbility::MACHINE->value]);

        return $user;
    }

    /**
     * Create a user with active shift for today
     */
    protected function createUserWithShiftForToday(): User
    {
        // This method will be fully implemented in Test 3
        // For now, just return a user
        return User::factory()->create();
    }

    /**
     * Assert that activity was logged
     */
    protected function assertActivityLogged(string $description, ?string $logName = 'database'): void
    {
        $this->assertDatabaseHas('activity_log', [
            'log_name' => $logName,
            'description' => $description,
        ]);
    }

    /**
     * Get valid user data for creation
     */
    protected function validUserData(): array
    {
        return [
            'employee_number' => '999999',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_active' => true,
        ];
    }

    /**
     * Get valid machine data for creation
     */
    protected function validMachineData(): array
    {
        return [
            'code' => 'TEST-MACHINE-999',
            'name' => 'Test Machine',
            'location' => 'Test Plant',
            'status' => 'active',
            'description' => 'Test description',
        ];
    }
}
