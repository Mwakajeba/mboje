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
        // Update the enum to include 'bank'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pos_sales MODIFY COLUMN payment_method ENUM('cash', 'card', 'mobile_money', 'mixed', 'bank') DEFAULT 'cash'");
        }
        // SQLite doesn't support modifying column types, so we skip this for SQLite
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'bank' from the enum
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pos_sales MODIFY COLUMN payment_method ENUM('cash', 'card', 'mobile_money', 'mixed') DEFAULT 'cash'");
        }
    }
};
