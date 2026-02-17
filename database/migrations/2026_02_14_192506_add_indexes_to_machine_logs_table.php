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
        Schema::table('machine_logs', function (Blueprint $table) {
            // Add index on machine_code for faster filtering by machine
            $table->index('machine_code');
            
            // Add index on event for faster filtering by event type
            $table->index('event');
            
            // Add composite index for common query pattern (machine + date)
            $table->index(['machine_code', 'created_at']);
            
            // Add composite index for user + date queries
            $table->index(['user_id', 'created_at']);
            
            // Add index on created_at for ordering
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machine_logs', function (Blueprint $table) {
            $table->dropIndex(['machine_code']);
            $table->dropIndex(['event']);
            $table->dropIndex(['machine_code', 'created_at']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['created_at']);
        });
    }
};
