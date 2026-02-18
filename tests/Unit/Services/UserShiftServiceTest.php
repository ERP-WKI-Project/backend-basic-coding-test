<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Machine;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use App\Services\UserShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserShiftServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserShiftService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserShiftService;
    }

    #[Test]
    public function it_can_create_assignment(): void
    {
        $user = User::factory()->create();
        $shift = Shift::factory()->create();
        $machine = Machine::factory()->create();

        $data = [
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'machine_id' => $machine->id,
            'shift_date' => now()->addDay()->format('Y-m-d'),
        ];

        $assignment = $this->service->create($data);

        $this->assertInstanceOf(UserShift::class, $assignment);
        $this->assertEquals($user->id, $assignment->user_id);
        $this->assertEquals($shift->id, $assignment->shift_id);
        $this->assertEquals($machine->id, $assignment->machine_id);
    }

    #[Test]
    public function it_prevents_duplicate_assignment_same_user_same_date(): void
    {
        $user = User::factory()->create();
        $shift1 = Shift::factory()->create();
        $shift2 = Shift::factory()->create();
        $machine = Machine::factory()->create();
        $date = now()->addDay();

        // Create first assignment
        UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift1->id,
            'machine_id' => $machine->id,
            'shift_date' => $date,
        ]);

        // Try to create second assignment for same user on same date
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User already has a shift assigned on this date');

        $this->service->create([
            'user_id' => $user->id,
            'shift_id' => $shift2->id,
            'machine_id' => $machine->id,
            'shift_date' => $date->format('Y-m-d'),
        ]);
    }

    #[Test]
    public function it_can_filter_by_user_id(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        UserShift::factory()->count(3)->forUser($user1)->create();
        UserShift::factory()->count(2)->forUser($user2)->create();

        $results = $this->service->all(['user_id' => $user1->id]);

        $this->assertCount(3, $results->items());
    }

    #[Test]
    public function it_can_filter_by_machine_id(): void
    {
        $machine1 = Machine::factory()->create();
        $machine2 = Machine::factory()->create();

        UserShift::factory()->count(4)->forMachine($machine1)->create();
        UserShift::factory()->count(2)->forMachine($machine2)->create();

        $results = $this->service->all(['machine_id' => $machine1->id]);

        $this->assertCount(4, $results->items());
    }

    #[Test]
    public function it_can_filter_by_date_range(): void
    {
        UserShift::factory()->forDate('2026-02-01')->create();
        UserShift::factory()->forDate('2026-02-15')->create();
        UserShift::factory()->forDate('2026-02-28')->create();

        $results = $this->service->all([
            'from_date' => '2026-02-10',
            'to_date' => '2026-02-20',
        ]);

        $this->assertCount(1, $results->items());
    }

    #[Test]
    public function it_can_get_active_shifts_for_user(): void
    {
        $user = User::factory()->create();
        $date = now()->format('Y-m-d');

        UserShift::factory()->forUser($user)->forDate($date)->create();
        UserShift::factory()->forUser($user)->forDate(now()->addDay()->format('Y-m-d'))->create();

        $results = $this->service->getActiveShiftsForUser($user->id, $date);

        $this->assertCount(1, $results);
    }
}
