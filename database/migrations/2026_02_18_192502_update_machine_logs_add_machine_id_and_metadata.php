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
        // Recreate table with proper structure for SQLite compatibility
        Schema::dropIfExists('machine_logs');

        Schema::create('machine_logs', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique()->index();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->text('log_message');
            $table->json('metadata')->nullable();
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_logs');

        Schema::create('machine_logs', function (Blueprint $table) {
            $table->id();
            $table->ulid();
            $table->string('machine_code');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->text('log_message');
            $table->datetimes();
        });
    }
};
