<?php

namespace Database\Seeders;

use App\Models\Machine;
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
        $this->call(PresetForCodingTestSeeder::class);
        $this->call(RoomSeeder::class);
        $this->call(ProductionLineSeeder::class);
        $this->call(MachineSeeder::class);

    }
}
