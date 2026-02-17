<?php

use App\Enums\MachineStatus;
use App\Models\Machine;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->shiftPagi = Shift::factory()->create(['day_of_week' => 1, 'name' => 'Shift Pagi']); // Monday
    $this->shiftSiang = Shift::factory()->create(['day_of_week' => 2, 'name' => 'Shift Siang']); // Tuesday

    Sanctum::actingAs(
        $this->user,
        [\App\Enums\SystemAbility::BACKOFFICE->value]
    );
});

describe('index', function () {
    test('mengembalikan daftar jadwal user beserta format paginasi yang valid', function () {
        UserShift::factory()->count(10)->create();

        $response = $this->getJson('/api/backoffice/v1/user-shifts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['user_id', 'shift_id', 'shift_date', 'machine_code', 'created_at', 'updated_at'],
                ],
                'meta',
            ])
            ->assertJsonFragment(['success' => true]);
    });

    test('mengembalikan daftar jadwal user berdasarkan user_id', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        UserShift::factory()->create(['user_id' => $user1->id]);
        UserShift::factory()->create(['user_id' => $user2->id]);
        UserShift::factory()->create(['user_id' => $user2->id]);

        $response = $this->getJson("/api/backoffice/v1/user-shifts?user_id={$user1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['user_id' => $user1->id]);
    });

    test('mengembalikan daftar jadwal user berdasarkan shift_id', function () {
        UserShift::factory()->create(['shift_id' => $this->shiftPagi->id]);
        UserShift::factory()->create(['shift_id' => $this->shiftSiang->id]);

        $response = $this->getJson("/api/backoffice/v1/user-shifts?shift_id={$this->shiftPagi->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['shift_id' => $this->shiftPagi->id]);
    });

    test('mengembalikan daftar jadwal user berdasarkan shift_date', function () {
        $date1 = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        $date2 = Carbon::now()->next(Carbon::TUESDAY)->format('Y-m-d');

        UserShift::factory()->create(['shift_date' => $date1, 'shift_id' => $this->shiftPagi->id]);
        UserShift::factory()->create(['shift_date' => $date2, 'shift_id' => $this->shiftSiang->id]);

        $response = $this->getJson("/api/backoffice/v1/user-shifts?shift_date={$date1}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['shift_date' => $date1]);
    });

    test('mengembalikan error validasi 422 jika filter user_id tidak valid (bukan angka)', function () {
        $response = $this->getJson('/api/backoffice/v1/user-shifts?user_id=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    });

    test('mengembalikan error validasi 422 jika filter user_id tidak ditemukan (non-existent)', function () {
        $response = $this->getJson('/api/backoffice/v1/user-shifts?user_id=99999');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    });

    test('mengembalikan error validasi 422 jika filter shift_date format salah', function () {
        $response = $this->getJson('/api/backoffice/v1/user-shifts?shift_date=invalid-date');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_date']);
    });
});

describe('store', function () {
    test('berhasil membuat jadwal user dan mengembalikan status 201', function () {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        $response = $this->postJson('/api/backoffice/v1/user-shifts', [
            'user_id' => $this->user->id,
            'shift_id' => $this->shiftPagi->id,
            'shift_date' => $nextMonday,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'success' => true,
                'message' => __('messages.user_shift_created'),
            ])
            ->assertJsonFragment([
                'user_id' => $this->user->id,
                'shift_id' => $this->shiftPagi->id,
                'shift_date' => $nextMonday,
            ]);
    });

    test('mengembalikan error validasi 422 jika hari tidak sesuai dengan shift', function () {
        $nextTuesday = Carbon::now()->next(Carbon::TUESDAY)->format('Y-m-d');

        $response = $this->postJson('/api/backoffice/v1/user-shifts', [
            'user_id' => $this->user->id,
            'shift_id' => $this->shiftPagi->id,
            'shift_date' => $nextTuesday,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_id']);
    });

    test('mengembalikan error validasi 422 jika user sudah punya shift di tanggal yang sama', function () {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        UserShift::create([
            'user_id' => $this->user->id,
            'shift_id' => $this->shiftPagi->id,
            'shift_date' => $nextMonday,
        ]);

        $response = $this->postJson('/api/backoffice/v1/user-shifts', [
            'user_id' => $this->user->id,
            'shift_id' => $this->shiftPagi->id,
            'shift_date' => $nextMonday,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_date']);
    });
    test('mengembalikan error validasi 422 jika referensi tidak ditemukan (Foreign Key)', function () {
        $response = $this->postJson('/api/backoffice/v1/user-shifts', [
            'user_id' => 99999,
            'shift_id' => 99999,
            'shift_date' => now()->format('Y-m-d'),
            'machine_code' => 'NONEXISTENT',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'shift_id', 'machine_code']);
    });
});

describe('show', function () {
    test('mengembalikan detail spesifik jadwal user dan mengembalikan status 200', function () {
        $userShift = UserShift::factory()->create();

        $response = $this->getJson("/api/backoffice/v1/user-shifts/{$userShift->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'message' => __('messages.user_shift_retrieved'),
                'user_id' => $userShift->user_id,
            ]);
    });

    test('mengembalikan status 404 jika jadwal user tidak ditemukan', function () {
        $response = $this->getJson('/api/backoffice/v1/user-shifts/999999');

        $response->assertStatus(404);
    });
});

describe('update', function () {
    test('berhasil mengupdate jadwal user dan mengembalikan status 200', function () {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        $userShift = UserShift::create([
            'user_id' => $this->user->id,
            'shift_id' => $this->shiftPagi->id,
            'shift_date' => $nextMonday,
        ]);

        $machine = Machine::factory()->create();

        $response = $this->putJson("/api/backoffice/v1/user-shifts/{$userShift->id}", [
            'machine_code' => $machine->code,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'message' => __('messages.user_shift_updated'),
                'machine_code' => $machine->code,
            ]);
    });

    test('berhasil self-update (mengirim shift_date yang sama)', function () {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        $userShift = UserShift::create([
            'user_id' => $this->user->id,
            'shift_id' => $this->shiftPagi->id,
            'shift_date' => $nextMonday,
        ]);

        $response = $this->putJson("/api/backoffice/v1/user-shifts/{$userShift->id}", [
            'shift_id' => $this->shiftPagi->id,
            'shift_date' => $nextMonday,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'shift_date' => $nextMonday,
            ]);
    });

    test('mengembalikan error validasi 422 jika hari baru tidak sesuai dengan shift', function () {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        $userShift = UserShift::create([
            'user_id' => $this->user->id,
            'shift_id' => $this->shiftPagi->id,
            'shift_date' => $nextMonday,
        ]);

        $nextTuesday = Carbon::now()->next(Carbon::TUESDAY)->format('Y-m-d');

        $response = $this->putJson("/api/backoffice/v1/user-shifts/{$userShift->id}", [
            'shift_date' => $nextTuesday,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_date']);
    });

    test('mengembalikan error validasi 422 jika shift_id baru tidak sesuai dengan shift_date lama', function () {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        $userShift = UserShift::create([
            'user_id' => $this->user->id,
            'shift_id' => $this->shiftPagi->id, // Monday
            'shift_date' => $nextMonday,
        ]);

        // Coba ubah shift ke Selasa (Shift Siang) tapi tanggal tetap Senin
        $response = $this->putJson("/api/backoffice/v1/user-shifts/{$userShift->id}", [
            'shift_id' => $this->shiftSiang->id, // Tuesday
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_id']);
    });

    test('mengembalikan error validasi 422 jika memindahkan jadwal ke tanggal dimana user sudah memiliki jadwal lain', function () {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        $nextTuesday = Carbon::now()->next(Carbon::TUESDAY)->format('Y-m-d');

        $shift1 = UserShift::create([
            'user_id' => $this->user->id,
            'shift_id' => $this->shiftPagi->id,
            'shift_date' => $nextMonday,
        ]);

        $shift2 = UserShift::create([
            'user_id' => $this->user->id,
            'shift_id' => $this->shiftSiang->id,
            'shift_date' => $nextTuesday,
        ]);

        $response = $this->putJson("/api/backoffice/v1/user-shifts/{$shift1->id}", [
            'shift_id' => $this->shiftSiang->id,
            'shift_date' => $nextTuesday,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_date']);
    });

    test('mengembalikan error validasi 422 jika mesin tidak aktif', function () {
        $machine = Machine::factory()->create(['status' => MachineStatus::INACTIVE->value]);
        $userShift = UserShift::factory()->create();

        $response = $this->putJson("/api/backoffice/v1/user-shifts/{$userShift->id}", [
            'machine_code' => $machine->code,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['machine_code']);
    });
});

describe('destroy', function () {
    test('berhasil menghapus jadwal user dan mengembalikan status 200', function () {
        $userShift = UserShift::factory()->create();

        $response = $this->deleteJson("/api/backoffice/v1/user-shifts/{$userShift->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true, 'message' => __('messages.user_shift_deleted')]);

        $this->assertDatabaseMissing('user_shifts', ['id' => $userShift->id]);
    });

    test('mengembalikan status 404 jika jadwal user tidak ditemukan', function () {
        $response = $this->deleteJson('/api/backoffice/v1/user-shifts/999999');

        $response->assertStatus(404);
    });

    test('mengembalikan status 422 jika mencoba menghapus jadwal masa lalu', function () {
        $userShift = UserShift::factory()->create([
            'shift_date' => Carbon::yesterday()->format('Y-m-d'),
        ]);

        $response = $this->deleteJson("/api/backoffice/v1/user-shifts/{$userShift->id}");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => __('messages.cannot_delete_past_shift')]);

        $this->assertDatabaseHas('user_shifts', ['id' => $userShift->id]);
    });
});

describe('unauthenticated', function () {
    test('mengembalikan status 401 jika user tidak terautentikasi', function () {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/backoffice/v1/user-shifts')->assertStatus(401);
        $this->postJson('/api/backoffice/v1/user-shifts', [])->assertStatus(401);
        $this->putJson('/api/backoffice/v1/user-shifts/1', [])->assertStatus(401);
        $this->deleteJson('/api/backoffice/v1/user-shifts/1')->assertStatus(401);
    });
});
