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
        // Modify the enum to include 'write_off'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `inventory_cost_layers` MODIFY COLUMN `transaction_type` ENUM('purchase', 'sale', 'adjustment_in', 'adjustment_out', 'opening_balance', 'transfer_in', 'transfer_out', 'write_off') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'write_off' from the enum (note: this will fail if there are existing records with 'write_off')
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `inventory_cost_layers` MODIFY COLUMN `transaction_type` ENUM('purchase', 'sale', 'adjustment_in', 'adjustment_out', 'opening_balance', 'transfer_in', 'transfer_out') NOT NULL");
        }
    }
};
