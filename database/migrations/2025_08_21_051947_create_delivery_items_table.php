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
        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('sales_order_item_id')->nullable(); // Link to sales order item
            $table->string('item_name');
            $table->string('item_code');
            $table->decimal('quantity', 10, 2);
            $table->decimal('picked_quantity', 10, 2)->default(0);
            $table->decimal('packed_quantity', 10, 2)->default(0);
            $table->decimal('delivered_quantity', 10, 2)->default(0);
            $table->string('unit_of_measure');
            $table->decimal('unit_weight', 10, 2)->default(0);
            $table->decimal('total_weight', 10, 2)->default(0);
            $table->string('location')->nullable(); // Warehouse location
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('picked')->default(false);
            $table->timestamp('picked_at')->nullable();
            $table->unsignedBigInteger('picked_by')->nullable();
            $table->boolean('packed')->default(false);
            $table->timestamp('packed_at')->nullable();
            $table->unsignedBigInteger('packed_by')->nullable();
            $table->boolean('delivered')->default(false);
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedBigInteger('delivered_by')->nullable();
            $table->text('picking_notes')->nullable();
            $table->text('packing_notes')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->timestamps();

            $table->foreign('delivery_id')->references('id')->on('deliveries')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('sales_order_item_id')->references('id')->on('sales_order_items')->onDelete('set null');
            $table->foreign('picked_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('packed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('delivered_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['delivery_id']);
            $table->index(['inventory_item_id']);
            $table->index(['sales_order_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_items');
    }
};
