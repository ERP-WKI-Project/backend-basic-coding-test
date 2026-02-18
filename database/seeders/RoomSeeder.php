<?php

namespace Database\Seeders;

use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dateTemplate = '1990-01-%02d';

        Room::class::Create([
            'ulid' => Str::ulid(Carbon::parse(sprintf($dateTemplate, 1))),
            'name' => 'Mixing Room',
        ]);
        Room::class::Create([
            'ulid' => Str::ulid(Carbon::parse(sprintf($dateTemplate, 2))),
            'name' => 'Filling Room',
        ]);
        Room::class::Create([
            'ulid' => Str::ulid(Carbon::parse(sprintf($dateTemplate, 3))),
            'name' => 'Packaging Room',
        ]);
    }
}
