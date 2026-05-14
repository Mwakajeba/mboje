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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('movement_type', ['opening_balance', 'transfer_in', 'transfer_out', 'sold', 'purchased', 'adjustment_in', 'adjustment_out', 'write_off']);
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->text('reason')->nullable();
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();
            $table->decimal('balance_before', 15, 2)->nullable();
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->date('movement_date')->nullable();
            $table->string('reference')->nullable(); // Original reference field
            $table->string('reference_number')->nullable();
            $table->string('reference_type')->nullable(); // purchase, sale, adjustment, etc.
            $table->foreignId('reference_id')->nullable(); // ID of the related record
            $table->timestamps();

            // Indexes
            $table->index(['branch_id', 'movement_type']);
            $table->index(['item_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
