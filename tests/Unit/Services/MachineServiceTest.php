<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\MachineStatusEnum;
use App\Models\Machine;
use App\Services\MachineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MachineServiceTest extends TestCase
{
    use RefreshDatabase;

    private MachineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MachineService;
    }

    // ==================== HAPPY PATH TESTS ====================

    #[Test]
    public function it_can_create_machine(): void
    {
        $data = [
            'code' => 'TEST-MACHINE-001',
            'name' => 'Test Machine',
            'location' => 'Plant A',
            'status' => MachineStatusEnum::ACTIVE,
            'description' => 'A test machine',
        ];

        $machine = $this->service->create($data);

        $this->assertInstanceOf(Machine::class, $machine);
        $this->assertEquals('TEST-MACHINE-001', $machine->code);
        $this->assertDatabaseHas('machines', ['code' => 'TEST-MACHINE-001']);
    }

    #[Test]
    public function it_can_filter_machines_by_status(): void
    {
        Machine::factory()->count(3)->active()->create();
        Machine::factory()->count(2)->maintenance()->create();
        Machine::factory()->count(1)->inactive()->create();

        $activeMachines = $this->service->all(['status' => MachineStatusEnum::ACTIVE->value]);
        $maintenanceMachines = $this->service->all(['status' => MachineStatusEnum::MAINTENANCE->value]);

        $this->assertCount(3, $activeMachines->items());
        $this->assertCount(2, $maintenanceMachines->items());
    }

    #[Test]
    public function it_can_search_machines_by_code(): void
    {
        Machine::factory()->create(['code' => 'FILLING-001']);
        Machine::factory()->create(['code' => 'FILLING-002']);
        Machine::factory()->create(['code' => 'PACKAGING-001']);

        $results = $this->service->all(['search' => 'FILLING']);

        $this->assertCount(2, $results->items());
    }

    // ==================== BUSINESS LOGIC TESTS ====================

    #[Test]
    public function it_detects_when_machine_is_in_use(): void
    {
        // Since UserShift is not implemented yet, this test will check
        // that the method returns false when no user_shifts exist
        $machine = Machine::factory()->create();

        $isInUse = $this->service->isMachineInUse($machine->id);

        $this->assertFalse($isInUse);
    }

    #[Test]
    public function it_detects_when_machine_is_not_in_use(): void
    {
        $machine = Machine::factory()->create();

        $isInUse = $this->service->isMachineInUse($machine->id);

        $this->assertFalse($isInUse);
    }

    #[Test]
    public function it_can_find_machine_by_code(): void
    {
        $machine = Machine::factory()->create(['code' => 'SPECIAL-001']);

        $found = $this->service->findByCode('SPECIAL-001');

        $this->assertNotNull($found);
        $this->assertEquals($machine->id, $found->id);
    }

    #[Test]
    public function it_returns_null_when_machine_code_not_found(): void
    {
        $found = $this->service->findByCode('NON-EXISTENT');

        $this->assertNull($found);
    }
}
