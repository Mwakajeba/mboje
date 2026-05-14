<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Optional: cost and selling price per item per location.
     * When resolving price: use location price if set, else branch price, else item default.
     */
    public function up(): void
    {
        Schema::create('inventory_item_location_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['item_id', 'location_id'], 'inventory_item_location_prices_item_location_unique');
            $table->index(['location_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_location_prices');
    }
};
