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
        Schema::create('inventory_expiry_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('inventory_locations')->onDelete('cascade');
            $table->string('batch_number')->nullable();
            $table->date('expiry_date');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->string('reference_type', 50); // 'purchase_invoice', 'cash_purchase', 'opening_balance', etc.
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_number')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['item_id', 'location_id']);
            $table->index('expiry_date');
            $table->index(['reference_type', 'reference_id']);
            $table->index(['item_id', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_expiry_tracking');
    }
};
