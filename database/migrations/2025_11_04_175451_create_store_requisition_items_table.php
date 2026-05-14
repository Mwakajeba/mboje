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
        Schema::create('store_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_requisition_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->decimal('quantity_requested', 10, 2);
            $table->decimal('quantity_approved', 10, 2)->default(0);
            $table->decimal('quantity_issued', 10, 2)->default(0);
            $table->decimal('quantity_returned', 10, 2)->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->string('unit_of_measure');
            $table->text('item_notes')->nullable();
            $table->text('issue_notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'partially_issued', 'fully_issued'])->default('pending');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('store_requisition_id')->references('id')->on('store_requisitions')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            
            // Indexes
            $table->index(['store_requisition_id']);
            $table->index(['inventory_item_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_requisition_items');
    }
};
