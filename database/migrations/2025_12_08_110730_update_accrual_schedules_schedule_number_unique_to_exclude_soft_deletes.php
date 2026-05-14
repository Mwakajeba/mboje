<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * MySQL doesn't support partial unique indexes with WHERE clauses like PostgreSQL.
     * The solution is to:
     * 1. Drop the unique constraint on schedule_number
     * 2. Rely on application logic in generateScheduleNumber() to ensure uniqueness
     *    The method now checks withTrashed() to avoid duplicates including soft-deleted records
     * 
     * This allows soft-deleted records to be reused while ensuring active records have unique numbers.
     */
    public function up(): void
    {
        // Drop the existing unique constraint
        // Uniqueness will be enforced by application logic in generateScheduleNumber()
        Schema::table('accrual_schedules', function (Blueprint $table) {
            $table->dropUnique(['schedule_number']);
        });
        
        // Add a regular index for performance (not unique)
        Schema::table('accrual_schedules', function (Blueprint $table) {
            $table->index('schedule_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the index
        Schema::table('accrual_schedules', function (Blueprint $table) {
            $table->dropIndex(['schedule_number']);
        });
        
        // Restore the original unique constraint
        Schema::table('accrual_schedules', function (Blueprint $table) {
            $table->unique('schedule_number');
        });
    }
};
