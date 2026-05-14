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
        // Only proceed if status column exists
        if (!Schema::hasColumn('retirements', 'status')) {
            Schema::table('retirements', function (Blueprint $table) {
                $table->enum('status', ['pending', 'checked', 'approved', 'closed', 'rejected'])->default('pending')->after('supporting_document');
            });
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            Schema::table('retirements', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            
            Schema::table('retirements', function (Blueprint $table) {
                $table->enum('status', ['pending', 'checked', 'approved', 'closed', 'rejected'])->default('pending')->after('supporting_document');
            });
        }
        // For SQLite, we skip this as the column already exists with the required values
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('retirements', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            
            Schema::table('retirements', function (Blueprint $table) {
                $table->enum('status', ['pending', 'checked', 'approved', 'rejected'])->default('pending')->after('supporting_document');
            });
        }
    }
};
