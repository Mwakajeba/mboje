<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores cost and selling price per item per branch.
     * When no row exists, fall back to the item's default cost_price / unit_price.
     * For location-specific prices later, add inventory_item_location_prices or use
     * branch_id + location_id with a separate table.
     */
    public function up(): void
    {
        Schema::create('inventory_item_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['item_id', 'branch_id'], 'inventory_item_prices_item_branch_unique');
            $table->index(['branch_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_item_prices');
    }
};
