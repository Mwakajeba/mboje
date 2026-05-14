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
        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->onDelete('set null');
            
            // Item details
            $table->string('item_name');
            $table->string('item_code');
            $table->text('description')->nullable();
            $table->string('unit_of_measure')->nullable();
            
            // Quantity and pricing
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            
            // VAT fields
            $table->enum('vat_type', ['inclusive', 'exclusive', 'no_vat'])->default('no_vat');
            $table->decimal('vat_rate', 5, 2)->default(18.00);
            $table->decimal('vat_amount', 15, 2)->default(0);
            
            // Discount fields
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            
            // Stock tracking
            $table->decimal('available_stock', 15, 2)->nullable();
            $table->decimal('reserved_stock', 15, 2)->nullable();
            $table->boolean('stock_available')->default(true);
            
            // Notes
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['sales_invoice_id', 'inventory_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
    }
};
