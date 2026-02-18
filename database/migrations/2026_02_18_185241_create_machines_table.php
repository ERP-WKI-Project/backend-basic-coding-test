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
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique()->index();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('location', 255)->nullable();
            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
