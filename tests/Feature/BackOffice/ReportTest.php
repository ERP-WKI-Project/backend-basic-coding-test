<?php

namespace Tests\Feature\BackOffice;

use App\Enums\SystemAbility;
use App\Enums\MachineLog\LogEntryTypeEnum;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Carbon\Carbon;
use Database\Seeders\PresetForCodingTestSeeder;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->seed(PresetForCodingTestSeeder::class);

    $this->adminUser = User::where('employee_number', '000001')->first();

    $this->machine = Machine::factory()->create([
        'code' => 'MCH-001',
        'name' => 'Test Machine',
        'location' => 'Factory Floor A',
    ]);

    $this->shift = Shift::firstOrCreate([
        'name' => 'Morning Shift',
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
    ]);
});

describe('User Machine Activity Report Index', function () {
    test('report_returns_user_machine_activities', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user = User::factory()->create([
            'employee_number' => '123456',
            'name' => 'John Doe',
        ]);

        $shiftDate = Carbon::today()->toDateString();

        UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $shiftDate,
            'machine_code' => 'MCH-001',
        ]);

        MachineLog::create([
            'user_id' => $user->id,
            'machine_code' => 'MCH-001',
            'event' => LogEntryTypeEnum::IN->value,
            'log_message' => 'Check in',
            'created_at' => Carbon::today()->setTime(8, 0, 0),
        ]);

        MachineLog::create([
            'user_id' => $user->id,
            'machine_code' => 'MCH-001',
            'event' => LogEntryTypeEnum::OUT->value,
            'log_message' => 'Check out',
            'created_at' => Carbon::today()->setTime(16, 0, 0),
        ]);

        $startDate = Carbon::today()->subWeek()->toDateString();
        $endDate = Carbon::today()->addWeek()->toDateString();

        $response = getJson("/api/backoffice/v1/report/user-machine-activity?start_date={$startDate}&end_date={$endDate}&user_id={$user->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'links', 'meta']);
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 15);

        $responseData = $response->json('data');

        $this->assertNotEmpty($responseData);
        $this->assertEquals('John Doe', $responseData[0]['user_name']);
        $this->assertEquals('123456', $responseData[0]['employee_number']);
    });

    test('report_filters_by_user_id', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user1 = User::factory()->create(['name' => 'User One']);
        $user2 = User::factory()->create(['name' => 'User Two']);

        $shiftDate = Carbon::today()->toDateString();

        UserShift::create([
            'user_id' => $user1->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $shiftDate,
        ]);

        UserShift::create([
            'user_id' => $user2->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $shiftDate,
        ]);

        $startDate = Carbon::today()->toDateString();
        $endDate = Carbon::today()->toDateString();

        $response = getJson("/api/backoffice/v1/report/user-machine-activity?start_date={$startDate}&end_date={$endDate}&user_id={$user1->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'links', 'meta']);

        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData);
        $this->assertEquals('User One', $responseData[0]['user_name']);
    });

    test('report_filters_by_machine_code', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user = User::factory()->create();

        $shiftDate = Carbon::today()->toDateString();

        UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $shiftDate,
            'machine_code' => 'MCH-001',
        ]);

        MachineLog::create([
            'user_id' => $user->id,
            'machine_code' => 'MCH-001',
            'event' => LogEntryTypeEnum::IN->value,
            'log_message' => 'Check in',
        ]);

        $startDate = Carbon::today()->toDateString();
        $endDate = Carbon::today()->toDateString();

        $response = getJson("/api/backoffice/v1/report/user-machine-activity?start_date={$startDate}&end_date={$endDate}&machine_code=MCH-001");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'links', 'meta']);

        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData);
        $this->assertNotEmpty($responseData[0]['shifts'][0]['activities']);
    });

    test('report_with_pagination', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        for ($i = 1; $i <= 20; $i++) {
            $user = User::factory()->create(['name' => "User {$i}"]);
            UserShift::create([
                'user_id' => $user->id,
                'shift_id' => $this->shift->id,
                'shift_date' => Carbon::today()->toDateString(),
            ]);
        }

        $startDate = Carbon::today()->toDateString();
        $endDate = Carbon::today()->toDateString();

        $response = getJson("/api/backoffice/v1/report/user-machine-activity?start_date={$startDate}&end_date={$endDate}&limit=5");

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 5);

        $responseData = $response->json('data');
        $this->assertCount(5, $responseData);
    });

    test('report_with_search_on_name', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user1 = User::factory()->create(['name' => 'Search John Doe']);
        $user2 = User::factory()->create(['name' => 'Search Jane Smith']);

        UserShift::create([
            'user_id' => $user1->id,
            'shift_id' => $this->shift->id,
            'shift_date' => Carbon::today()->toDateString(),
        ]);

        UserShift::create([
            'user_id' => $user2->id,
            'shift_id' => $this->shift->id,
            'shift_date' => Carbon::today()->toDateString(),
        ]);

        $startDate = Carbon::today()->toDateString();
        $endDate = Carbon::today()->toDateString();

        $response = getJson("/api/backoffice/v1/report/user-machine-activity?start_date={$startDate}&end_date={$endDate}&search=John");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'links', 'meta']);

        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData);
        $this->assertEquals('Search John Doe', $responseData[0]['user_name']);
    });

    test('report_with_search_on_employee_number', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $user1 = User::factory()->create(['employee_number' => '999111']);
        $user2 = User::factory()->create(['employee_number' => '999222']);

        UserShift::create([
            'user_id' => $user1->id,
            'shift_id' => $this->shift->id,
            'shift_date' => Carbon::today()->toDateString(),
        ]);

        UserShift::create([
            'user_id' => $user2->id,
            'shift_id' => $this->shift->id,
            'shift_date' => Carbon::today()->toDateString(),
        ]);

        $startDate = Carbon::today()->toDateString();
        $endDate = Carbon::today()->toDateString();

        $response = getJson("/api/backoffice/v1/report/user-machine-activity?start_date={$startDate}&end_date={$endDate}&search=999111");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'links', 'meta']);

        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData);
        $this->assertEquals('999111', $responseData[0]['employee_number']);
    });

    test('report_returns_empty_when_no_data', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $startDate = Carbon::today()->subMonth()->toDateString();
        $endDate = Carbon::today()->subMonth()->toDateString();

        $response = getJson("/api/backoffice/v1/report/user-machine-activity?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    });

    test('report_validates_required_dates', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = getJson('/api/backoffice/v1/report/user-machine-activity');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    });

    test('report_validates_date_format', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = getJson('/api/backoffice/v1/report/user-machine-activity?start_date=invalid&end_date=2024-01-01');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    });

    test('report_validates_end_date_after_start_date', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::BACKOFFICE->value]);

        $response = getJson('/api/backoffice/v1/report/user-machine-activity?start_date=2024-01-10&end_date=2024-01-01');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    });

    test('report_unauthenticated_redirects', function () {
        $response = getJson('/api/backoffice/v1/report/user-machine-activity?start_date=2024-01-01&end_date=2024-01-31');

        $this->assertContains($response->getStatusCode(), [401, 403, 500]);
    });

    test('report_wrong_ability', function () {
        Sanctum::actingAs($this->adminUser, [SystemAbility::MACHINE->value]);

        $response = getJson('/api/backoffice/v1/report/user-machine-activity?start_date=2024-01-01&end_date=2024-01-31');

        $response->assertStatus(403);
    });
});
