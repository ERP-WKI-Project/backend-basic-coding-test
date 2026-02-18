<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PresetForCodingTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a dummy user with employee_number '000001'
        $presetUser = User::firstOrCreate([
            'employee_number' => '000001',
        ], [
            'name' => 'Dummy Employee',
            'password' => bcrypt('password'),
            'ulid'     => (string) str()->ulid()
        ]);

        $dateTemplate = '1990-01-%02d';

        // Create some preset shifts if they don't exist
        if (Shift::count() === 0) {
            for ($day = 1; $day <= 7; $day++) {
                Shift::create([
                    'ulid' => Str::ulid(Carbon::parse(sprintf($dateTemplate, $day))),
                    'day_of_week' => $day,
                    'name' => 'Shift Pagi',
                    'start_time' => '07:00:00',
                    'end_time' => '15:00:00',
                ]);

                Shift::create([
                    'ulid' => Str::ulid(Carbon::parse(sprintf($dateTemplate, $day))),
                    'day_of_week' => $day,
                    'name' => 'Shift Siang',
                    'start_time' => '15:00:00',
                    'end_time' => '23:00:00',
                ]);

                Shift::create([
                    'ulid' => Str::ulid(Carbon::parse(sprintf($dateTemplate, $day))),
                    'day_of_week' => $day,
                    'name' => 'Shift Malam',
                    'start_time' => '23:00:00',
                    'end_time' => '07:00:00',
                ]);
            }
        }

        // Assign all shifts to the preset user for all days if not already assigned
        if ($presetUser->shifts()->count() === 0) {
            $shifts = Shift::where(function ($query) use ($dateTemplate) {
                $query->orWhere('ulid', 'like', substr(Str::ulid(Carbon::parse(sprintf($dateTemplate, 1))), 0, 10) . '%');
                $query->orWhere('ulid', 'like', substr(Str::ulid(Carbon::parse(sprintf($dateTemplate, 2))), 0, 10) . '%');
                $query->orWhere('ulid', 'like', substr(Str::ulid(Carbon::parse(sprintf($dateTemplate, 3))), 0, 10) . '%');
                $query->orWhere('ulid', 'like', substr(Str::ulid(Carbon::parse(sprintf($dateTemplate, 4))), 0, 10) . '%');
                $query->orWhere('ulid', 'like', substr(Str::ulid(Carbon::parse(sprintf($dateTemplate, 5))), 0, 10) . '%');
                $query->orWhere('ulid', 'like', substr(Str::ulid(Carbon::parse(sprintf($dateTemplate, 6))), 0, 10) . '%');
                $query->orWhere('ulid', 'like', substr(Str::ulid(Carbon::parse(sprintf($dateTemplate, 7))), 0, 10) . '%');
            })->get();

            $startDate = now()->startOfMonth()->dayOfMonth;
            $stepShift = ['Shift Pagi', 'Shift Siang', 'Shift Malam'];
            $endDate = now()->endOfMonth()->dayOfMonth;

            for ($dateOfMonth = $startDate; $dateOfMonth <= $endDate; $dateOfMonth++) {
                $date = now()->startOfMonth()->addDays($dateOfMonth - 1);
                $dayOfWeek = $date->dayOfWeekIso; // 1 (Monday) to 7 (Sunday)
                $idx = ($dateOfMonth - 1) % 4;
                if ($idx === 3) continue; // Off day

                $shiftName = $stepShift[$idx];
                $shift = $shifts->where('day_of_week', $dayOfWeek)
                    ->where('name', $shiftName)
                    ->first();
                
                if (!$shift) continue;
                UserShift::create([
                    'user_id' => $presetUser->id,
                    'shift_id' => $shift->id,
                    'shift_date' => $date,
                    'machine_code' => 'FILLING-MACHINE-001',
                ]);
            }
        }
    }
}
