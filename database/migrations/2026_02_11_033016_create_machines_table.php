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
            $table->char('code', 7)->unique()->index();          // MC-FILL-01
            $table->string('name');                    // Filling Machine 1
            $table->string('type')->index();                    // filling / mixer / labeling
            $table->string('brand')->nullable();       // Bosch, GEA, etc
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();

            $table->unsignedInteger('capacity_per_hour')->nullable(); // units/hour
            $table->string('capacity_unit')->nullable(); // pcs / kg / liter

            $table->foreignId('production_line_id')->nullable()->constrained();
            $table->foreignId('room_id')->nullable()->constrained();   // cleanroom area

            $table->date('purchase_date')->nullable();
            $table->date('installation_date')->nullable();
            $table->string('status')->default('active')->index();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
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
