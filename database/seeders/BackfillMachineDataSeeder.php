<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\UserShift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BackfillMachineDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $existingCodes = Machine::pluck('code')->toArray();
            $shiftCodes = UserShift::whereNotNull('machine_code')->distinct()->pluck('machine_code')->toArray();

            $missingCodes = array_diff($shiftCodes, $existingCodes);

            foreach ($missingCodes as $code) {
                Machine::insert([
                    'ulid' => (string) Str::ulid(),
                    'code' => $code,
                    'name' => 'Imported Machine ' . $code,
                    'status' => 'active',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            UserShift::where(function ($q) {
                $q->whereNull('machine_id')
                    ->orWhereNull('ulid');
            })
                ->chunkById(100, function ($shifts) {
                    foreach ($shifts as $shift) {
                        $updateData = [];

                        if (empty($shift->ulid)) {
                            $updateData['ulid'] = (string) Str::ulid();
                        }

                        if (empty($shift->machine_id) && !empty($shift->machine_code)) {
                            $machine = Machine::where('code', $shift->machine_code)->first();
                            if ($machine) {
                                $updateData['machine_id'] = $machine->id;
                            }
                        }

                        if (!empty($updateData)) {
                            UserShift::where('id', $shift->id)
                                ->update($updateData);
                        }
                    }
                });

            DB::commit();
            optional($this->command)->info('Backfill machine data and ULIDs completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("BackfillMachineDataSeeder Failed: " . $e->getMessage());
            optional($this->command)->error("Seeder failed: " . $e->getMessage());

            throw $e;
        }
    }
}
