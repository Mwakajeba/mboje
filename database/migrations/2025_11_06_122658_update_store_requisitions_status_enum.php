<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Update the status enum to include fully_issued
            DB::statement("ALTER TABLE store_requisitions MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'partially_issued', 'fully_issued', 'completed', 'cancelled') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Revert to original enum
            DB::statement("ALTER TABLE store_requisitions MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'partially_issued', 'completed', 'cancelled') DEFAULT 'pending'");
        }
    }
};
