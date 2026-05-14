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
        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->string('reference')->nullable(); // Reference to movement, invoice, etc.
            $table->enum('transaction_type', ['purchase', 'sale', 'adjustment_in', 'adjustment_out', 'opening_balance', 'transfer_in', 'transfer_out']);
            $table->decimal('quantity', 15, 4); // Quantity in this layer
            $table->decimal('remaining_quantity', 15, 4); // Remaining quantity (for FIFO)
            $table->decimal('unit_cost', 15, 4); // Cost per unit
            $table->decimal('total_cost', 15, 2); // Total cost for this layer
            $table->date('transaction_date');
            $table->boolean('is_consumed')->default(false); // Whether this layer is fully consumed (FIFO)
            $table->timestamps();

            // Indexes for performance
            $table->index(['item_id', 'transaction_date']);
            $table->index(['item_id', 'is_consumed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_layers');
    }
};
