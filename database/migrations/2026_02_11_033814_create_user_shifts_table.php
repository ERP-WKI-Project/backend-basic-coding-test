<?php

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
        Schema::create('user_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->date('shift_date')->index();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete()->nullable()->index();
            $table->datetimes();

            $table->index(['user_id', 'shift_date']);
            $table->index(['machine_id', 'shift_date']);
            $table->index(['shift_id', 'shift_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_shifts');
    }
};
