<?php

namespace Database\Seeders;

use App\Models\MachineLog;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoomSeeder::class);
        $this->call(ProductionLineSeeder::class);
        $this->call(MachineSeeder::class);
        $this->call(PresetForCodingTestSeeder::class);

        if ($this->command && $this->command->confirm('Install dummy data?', false)) {
            $this->command->info('[START] Install dummy data..........');

            User::factory(config('dummy.dummies.users'))->create();
            MachineLog::factory(config('dummy.dummies.machinelog'))->create();

            $this->command->info('[DONE ] Install dummy data.');

        }
    }
}
