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
            $table->foreign('machine_code')
                ->references('machine_code')
                ->on('machines')
                ->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event');
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
