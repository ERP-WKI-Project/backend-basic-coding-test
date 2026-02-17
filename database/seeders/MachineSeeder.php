<?php

namespace Database\Seeders;

use App\Models\Machine;
use Illuminate\Database\Seeder;

class MachineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Machine::insert([

            [
                'code' => 'MIX-001',
                'name' => 'Vacuum Emulsifying Mixer 500L',
                'type' => 'Mixer',
                'brand' => 'Ginhong',
                'model' => 'GH-500',
                'serial_number' => 'GH500-2023-001',
                'capacity_per_hour' => 500,
                'capacity_unit' => 'kg',
                'production_line_id' => 1,
                'room_id' => 1,
                'purchase_date' => '2023-01-10',
                'installation_date' => '2023-02-01',
                'status' => 'active',
                'notes' => 'Main sunscreen bulk mixer',
            ],

            [
                'code' => 'HMG-001',
                'name' => 'Inline Homogenizer',
                'type' => 'Homogenizer',
                'brand' => 'IKA',
                'model' => '2000',
                'serial_number' => 'IKA-2023-221',
                'capacity_per_hour' => 1000,
                'capacity_unit' => 'kg',
                'production_line_id' => 1,
                'room_id' => 1,
                'purchase_date' => '2023-01-12',
                'installation_date' => '2023-02-05',
                'status' => 'active',
                'notes' => 'Emulsion smoothing',
            ],

            [
                'code' => 'TNK-001',
                'name' => 'Bulk Holding Tank 1000L',
                'type' => 'Storage Tank',
                'brand' => 'LocalFab',
                'model' => 'ST-1000',
                'serial_number' => 'ST1000-332',
                'capacity_per_hour' => 1000,
                'capacity_unit' => 'kg',
                'production_line_id' => 1,
                'room_id' => 1,
                'purchase_date' => '2023-01-20',
                'installation_date' => '2023-02-10',
                'status' => 'active',
                'notes' => 'Bulk storage before filling',
            ],

            [
                'code' => 'FIL-T01',
                'name' => 'Automatic Tube Filling Machine',
                'type' => 'Tube Filler',
                'brand' => 'Norden',
                'model' => 'NM702',
                'serial_number' => 'NM702-8842',
                'capacity_per_hour' => 2400,
                'capacity_unit' => 'pcs',
                'production_line_id' => 2,
                'room_id' => 2,
                'purchase_date' => '2023-03-01',
                'installation_date' => '2023-03-20',
                'status' => 'active',
                'notes' => '50ml sunscreen tube',
            ],

            [
                'code' => 'SEA-T01',
                'name' => 'Tube Sealing Machine',
                'type' => 'Tube Sealer',
                'brand' => 'Axomatic',
                'model' => 'AXO-300',
                'serial_number' => 'AXO300-921',
                'capacity_per_hour' => 2600,
                'capacity_unit' => 'pcs',
                'production_line_id' => 2,
                'room_id' => 2,
                'purchase_date' => '2023-03-05',
                'installation_date' => '2023-03-22',
                'status' => 'active',
                'notes' => 'Hot air tube sealing',
            ],

            [
                'code' => 'FIL-B01',
                'name' => 'Lotion Bottle Filling Machine',
                'type' => 'Bottle Filler',
                'brand' => 'Accutek',
                'model' => 'AFM-4',
                'serial_number' => 'AFM4-3321',
                'capacity_per_hour' => 1800,
                'capacity_unit' => 'pcs',
                'production_line_id' => 2,
                'room_id' => 2,
                'purchase_date' => '2023-04-01',
                'installation_date' => '2023-04-18',
                'status' => 'active',
                'notes' => '100ml sunscreen bottle',
            ],

            [
                'code' => 'LAB-001',
                'name' => 'Automatic Labeling Machine',
                'type' => 'Labeler',
                'brand' => 'Herma',
                'model' => '500',
                'serial_number' => 'HER500-22',
                'capacity_per_hour' => 3000,
                'capacity_unit' => 'pcs',
                'production_line_id' => 3,
                'room_id' => 3,
                'purchase_date' => '2023-05-01',
                'installation_date' => '2023-05-15',
                'status' => 'active',
                'notes' => 'Tube labeling',
            ],

            [
                'code' => 'PRN-001',
                'name' => 'Inkjet Batch Coding Printer',
                'type' => 'Coder',
                'brand' => 'Domino',
                'model' => 'A320i',
                'serial_number' => 'DOM-A320-77',
                'capacity_per_hour' => 5000,
                'capacity_unit' => 'pcs',
                'production_line_id' => 3,
                'room_id' => 3,
                'purchase_date' => '2023-05-10',
                'installation_date' => '2023-05-18',
                'status' => 'active',
                'notes' => 'Batch & expiry printing',
            ],

            [
                'code' => 'CTN-001',
                'name' => 'Semi Auto Cartoning Machine',
                'type' => 'Cartoner',
                'brand' => 'Marchesini',
                'model' => 'MA150',
                'serial_number' => 'MA150-912',
                'capacity_per_hour' => 1200,
                'capacity_unit' => 'pcs',
                'production_line_id' => 3,
                'room_id' => 3,
                'purchase_date' => '2023-06-01',
                'installation_date' => '2023-06-20',
                'status' => 'active',
                'notes' => 'Secondary packaging',
            ],

        ]);
    }
}
