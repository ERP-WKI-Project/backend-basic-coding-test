<?php

use App\Models\UserShift;
use Database\Seeders\BackfillMachineDataSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_shifts', function (Blueprint $table) {
            $table->ulid()->nullable();
            $table->foreignId('machine_id')->nullable()->constrained('machines');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->softDeletes();
        });

        $seeder = new BackfillMachineDataSeeder();
        $seeder->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_shifts', function (Blueprint $table) {
            $table->dropForeign(['machine_id', 'created_by']);
            $table->dropColumn([
                'ulid',
                'machine_id',
                'created_by'
            ]);
            $table->dropSoftDeletes();
        });
    }
};
