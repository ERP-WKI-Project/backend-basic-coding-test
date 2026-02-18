<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserService;
    }

    // ==================== HAPPY PATH TESTS ====================

    #[Test]
    public function it_can_create_user_with_valid_data(): void
    {
        $data = [
            'employee_number' => '123456',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'is_active' => true,
        ];

        $user = $this->service->create($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'employee_number' => '123456',
            'email' => 'john@example.com',
        ]);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotNull($user->ulid);
    }

    #[Test]
    public function it_can_list_all_users_with_pagination(): void
    {
        User::factory()->count(25)->create();

        $result = $this->service->all(['per_page' => 10]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(25, $result->total());
    }

    #[Test]
    public function it_can_find_user_by_ulid(): void
    {
        $user = User::factory()->create();

        $found = $this->service->find($user->ulid);

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    #[Test]
    public function it_can_update_user_without_changing_password(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $oldPassword = $user->password;

        $updated = $this->service->update($user->ulid, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertEquals($oldPassword, $updated->password);
    }

    #[Test]
    public function it_can_delete_user(): void
    {
        $user = User::factory()->create();

        $result = $this->service->delete($user->ulid);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    // ==================== FILTER & SEARCH TESTS ====================

    #[Test]
    public function it_can_filter_users_by_search_term(): void
    {
        User::factory()->create(['name' => 'John Smith', 'email' => 'john@test.com']);
        User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@test.com']);
        User::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@test.com']);

        $results = $this->service->all(['search' => 'john']);

        $this->assertCount(2, $results->items());
    }

    #[Test]
    public function it_can_filter_users_by_active_status(): void
    {
        User::factory()->count(3)->active()->create();
        User::factory()->count(2)->inactive()->create();

        $activeUsers = $this->service->all(['is_active' => true]);
        $inactiveUsers = $this->service->all(['is_active' => false]);

        $this->assertCount(3, $activeUsers->items());
        $this->assertCount(2, $inactiveUsers->items());
    }

    #[Test]
    public function it_can_search_by_employee_number(): void
    {
        User::factory()->create(['employee_number' => '999999']);
        User::factory()->count(5)->create();

        $results = $this->service->all(['search' => '999999']);

        $this->assertCount(1, $results->items());
        $this->assertEquals('999999', $results->first()->employee_number);
    }

    // ==================== EDGE CASE TESTS ====================

    #[Test]
    public function it_returns_null_when_user_not_found(): void
    {
        $result = $this->service->find('invalid-ulid-string');

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_list_users_without_pagination(): void
    {
        User::factory()->count(5)->create();

        $result = $this->service->all(['paginate' => false]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(5, $result);
    }

    #[Test]
    public function it_hashes_password_when_provided_on_update(): void
    {
        $user = User::factory()->create(['password' => Hash::make('oldpass')]);

        $updated = $this->service->update($user->ulid, ['password' => 'newpass123']);

        $this->assertTrue(Hash::check('newpass123', $updated->password));
    }

    #[Test]
    public function it_can_find_user_by_employee_number(): void
    {
        $user = User::factory()->create(['employee_number' => '555555']);

        $found = $this->service->findByEmployeeNumber('555555');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }
}
