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
        Schema::table('inventory_items', function (Blueprint $table) {
            // Change stock-related fields from integer to decimal to support decimal quantities
            $table->decimal('minimum_stock', 15, 2)->nullable()->change();
            $table->decimal('reorder_level', 15, 2)->nullable()->change();
            $table->decimal('maximum_stock', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            // Revert back to integer (this will truncate decimal values)
            $table->integer('minimum_stock')->nullable()->change();
            $table->integer('reorder_level')->nullable()->change();
            $table->integer('maximum_stock')->nullable()->change();
        });
    }
};