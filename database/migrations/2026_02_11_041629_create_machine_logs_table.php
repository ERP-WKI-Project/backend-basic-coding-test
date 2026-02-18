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
        Schema::create('machine_logs', function (Blueprint $table) {
            $table->id();
            $table->ulid();
            $table->string('machine_code')->index();
            $table->foreignId('machine_id')->nullable()->index()->after('user_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->index();
            $table->string('event')->index();
            $table->text('log_message');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_logs');
    }
};
