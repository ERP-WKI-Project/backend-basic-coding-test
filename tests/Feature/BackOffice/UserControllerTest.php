<?php

declare(strict_types=1);

namespace Tests\Feature\BackOffice;

use App\Enums\SystemAbility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authenticateBackOffice(): User
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin, [SystemAbility::BACKOFFICE->value]);

        return $admin;
    }

    private function authenticateMachine(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [SystemAbility::MACHINE->value]);

        return $user;
    }

    // ==================== INDEX TESTS ====================

    #[Test]
    public function it_can_list_all_users_with_pagination(): void
    {
        $this->authenticateBackOffice();
        User::factory()->count(24)->create(); // 24 + 1 admin = 25

        $response = $this->getJson('/api/backoffice/v1/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['ulid', 'employee_number', 'name', 'email', 'is_active', 'created_at'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonPath('meta.total', 25);
    }

    #[Test]
    public function it_can_filter_users_by_search(): void
    {
        $this->authenticateBackOffice();
        User::factory()->create(['name' => 'John Unique']);
        User::factory()->count(10)->create();

        $response = $this->getJson('/api/backoffice/v1/users?search=John+Unique');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'John Unique');
    }

    #[Test]
    public function it_can_filter_users_by_status(): void
    {
        $this->authenticateBackOffice(); // admin is active
        User::factory()->count(4)->active()->create(); // 4 + 1 admin = 5 active
        User::factory()->count(3)->inactive()->create();

        $response = $this->getJson('/api/backoffice/v1/users?is_active=1');

        $response->assertOk()
            ->assertJsonPath('meta.total', 5);
    }

    #[Test]
    public function it_returns_401_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/backoffice/v1/users');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_returns_403_when_wrong_ability(): void
    {
        $this->authenticateMachine();

        $response = $this->getJson('/api/backoffice/v1/users');

        $response->assertForbidden();
    }

    // ==================== STORE TESTS ====================

    #[Test]
    public function it_can_create_user_with_valid_data(): void
    {
        $this->authenticateBackOffice();

        $response = $this->postJson('/api/backoffice/v1/users', [
            'employee_number' => '123456',
            'name' => 'New Employee',
            'email' => 'new@example.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.employee_number', '123456')
            ->assertJsonPath('data.name', 'New Employee')
            ->assertJsonStructure(['data' => ['ulid', 'created_at']]);

        $this->assertDatabaseHas('users', [
            'employee_number' => '123456',
            'email' => 'new@example.com',
        ]);
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        $this->authenticateBackOffice();

        $response = $this->postJson('/api/backoffice/v1/users', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['employee_number', 'name', 'email', 'password']);
    }

    #[Test]
    public function it_validates_employee_number_format(): void
    {
        $this->authenticateBackOffice();

        $response = $this->postJson('/api/backoffice/v1/users', [
            'employee_number' => '123', // Too short
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['employee_number']);
    }

    #[Test]
    public function it_prevents_duplicate_employee_number(): void
    {
        $this->authenticateBackOffice();
        User::factory()->create(['employee_number' => '999999']);

        $response = $this->postJson('/api/backoffice/v1/users', [
            'employee_number' => '999999',
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['employee_number']);
    }

    #[Test]
    public function it_prevents_duplicate_email(): void
    {
        $this->authenticateBackOffice();
        User::factory()->create(['email' => 'exists@test.com']);

        $response = $this->postJson('/api/backoffice/v1/users', [
            'employee_number' => '111111',
            'name' => 'Test',
            'email' => 'exists@test.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function it_logs_activity_on_create(): void
    {
        $this->authenticateBackOffice();

        $this->postJson('/api/backoffice/v1/users', [
            'employee_number' => '777777',
            'name' => 'Logged User',
            'email' => 'logged@test.com',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'database',
            'description' => 'Created new User',
        ]);
    }

    // ==================== SHOW TESTS ====================

    #[Test]
    public function it_can_show_user_details(): void
    {
        $this->authenticateBackOffice();
        $user = User::factory()->create();

        $response = $this->getJson("/api/backoffice/v1/users/{$user->ulid}");

        $response->assertOk()
            ->assertJsonPath('data.ulid', $user->ulid)
            ->assertJsonPath('data.name', $user->name);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_user(): void
    {
        $this->authenticateBackOffice();

        $response = $this->getJson('/api/backoffice/v1/users/invalid-ulid');

        $response->assertNotFound();
    }

    // ==================== UPDATE TESTS ====================

    #[Test]
    public function it_can_update_user(): void
    {
        $this->authenticateBackOffice();
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/backoffice/v1/users/{$user->ulid}", [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function it_can_update_password(): void
    {
        $this->authenticateBackOffice();
        $user = User::factory()->create();
        $oldPassword = $user->password;

        $response = $this->putJson("/api/backoffice/v1/users/{$user->ulid}", [
            'password' => 'newpassword123',
        ]);

        $response->assertOk();

        $user->refresh();
        $this->assertNotEquals($oldPassword, $user->password);
    }

    #[Test]
    public function it_ignores_null_password_on_update(): void
    {
        $this->authenticateBackOffice();
        $user = User::factory()->create();
        $oldPassword = $user->password;

        $response = $this->putJson("/api/backoffice/v1/users/{$user->ulid}", [
            'name' => 'New Name',
            'password' => null,
        ]);

        $response->assertOk();

        $user->refresh();
        $this->assertEquals($oldPassword, $user->password);
    }

    #[Test]
    public function it_prevents_duplicate_email_on_update(): void
    {
        $this->authenticateBackOffice();
        $user1 = User::factory()->create(['email' => 'user1@test.com']);
        $user2 = User::factory()->create(['email' => 'user2@test.com']);

        $response = $this->putJson("/api/backoffice/v1/users/{$user1->ulid}", [
            'email' => 'user2@test.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    // ==================== DELETE TESTS ====================

    #[Test]
    public function it_can_delete_user(): void
    {
        $this->authenticateBackOffice();
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/backoffice/v1/users/{$user->ulid}");

        $response->assertOk()
            ->assertJsonPath('message', 'User deleted successfully');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    #[Test]
    public function it_returns_404_when_deleting_nonexistent_user(): void
    {
        $this->authenticateBackOffice();

        $response = $this->deleteJson('/api/backoffice/v1/users/invalid-ulid');

        $response->assertNotFound();
    }

    #[Test]
    public function it_logs_activity_on_delete(): void
    {
        $this->authenticateBackOffice();
        $user = User::factory()->create();

        $this->deleteJson("/api/backoffice/v1/users/{$user->ulid}");

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'database',
            'description' => 'Deleted User',
        ]);
    }
}
