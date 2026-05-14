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
            Schema::table('credit_notes', function (Blueprint $table) {
                // Update the vat_type enum to include 'no_vat'
                DB::statement("ALTER TABLE credit_notes MODIFY COLUMN vat_type ENUM('inclusive', 'exclusive', 'no_vat') DEFAULT 'no_vat'");
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('credit_notes', function (Blueprint $table) {
                // Revert the vat_type enum to exclude 'no_vat'
                DB::statement("ALTER TABLE credit_notes MODIFY COLUMN vat_type ENUM('inclusive', 'exclusive') DEFAULT 'inclusive'");
            });
        }
    }
};
