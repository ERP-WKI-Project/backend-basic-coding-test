<?php

namespace Database\Seeders;

use App\Models\ProductionLine;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductionLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dateTemplate = '1990-01-%02d';

        ProductionLine::Create([
            'ulid' => Str::ulid(Carbon::parse(sprintf($dateTemplate, 1))),
            'name' => 'Bulk Mixing',
        ]);

        ProductionLine::Create([
            'ulid' => Str::ulid(Carbon::parse(sprintf($dateTemplate, 2))),
            'name' => 'Tube Sunscreen',
        ]);

        ProductionLine::Create([
            'ulid' => Str::ulid(Carbon::parse(sprintf($dateTemplate, 3))),
            'name' => 'Bottle Sunscreen',
        ]);
    }
}
