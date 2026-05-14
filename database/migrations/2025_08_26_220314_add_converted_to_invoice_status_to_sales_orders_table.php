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
            Schema::table('sales_orders', function (Blueprint $table) {
                // Modify the status enum to include 'converted_to_invoice'
                DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('draft', 'pending_approval', 'approved', 'in_production', 'ready_for_delivery', 'delivered', 'cancelled', 'on_hold', 'converted_to_invoice') DEFAULT 'draft'");
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('sales_orders', function (Blueprint $table) {
                // Revert the status enum to exclude 'converted_to_invoice'
                DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('draft', 'pending_approval', 'approved', 'in_production', 'ready_for_delivery', 'delivered', 'cancelled', 'on_hold') DEFAULT 'draft'");
            });
        }
    }
};
