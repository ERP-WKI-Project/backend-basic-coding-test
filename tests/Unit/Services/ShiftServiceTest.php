<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShiftServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShiftService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShiftService;
    }

    #[Test]
    public function it_can_create_shift(): void
    {
        $data = [
            'day_of_week' => 1,
            'name' => 'Shift Pagi',
            'start_time' => '07:00:00',
            'end_time' => '15:00:00',
        ];

        $shift = $this->service->create($data);

        $this->assertInstanceOf(Shift::class, $shift);
        $this->assertEquals('Shift Pagi', $shift->name);
        $this->assertEquals(1, $shift->day_of_week);
        $this->assertDatabaseHas('shifts', ['name' => 'Shift Pagi']);
    }

    #[Test]
    public function it_can_filter_shifts_by_day_of_week(): void
    {
        Shift::factory()->create(['day_of_week' => 1, 'name' => 'Shift Senin']);
        Shift::factory()->create(['day_of_week' => 2, 'name' => 'Shift Selasa']);
        Shift::factory()->create(['day_of_week' => 1, 'name' => 'Shift Senin 2']);

        $results = $this->service->all(['day_of_week' => 1]);

        $this->assertCount(2, $results->items());
    }

    #[Test]
    public function it_can_get_shifts_by_day(): void
    {
        Shift::factory()->create(['day_of_week' => 3, 'start_time' => '07:00:00']);
        Shift::factory()->create(['day_of_week' => 3, 'start_time' => '15:00:00']);
        Shift::factory()->create(['day_of_week' => 4, 'start_time' => '07:00:00']);

        $shifts = $this->service->getShiftsByDay(3);

        $this->assertInstanceOf(Collection::class, $shifts);
        $this->assertCount(2, $shifts);
    }

    #[Test]
    public function it_can_update_shift(): void
    {
        $shift = Shift::factory()->create(['name' => 'Old Name']);

        $updated = $this->service->update($shift->ulid, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
    }

    #[Test]
    public function it_can_delete_shift(): void
    {
        $shift = Shift::factory()->create();

        $result = $this->service->delete($shift->ulid);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }
}
