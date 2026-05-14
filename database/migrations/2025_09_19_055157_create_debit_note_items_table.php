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
        Schema::create('debit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debit_note_id')->constrained('debit_notes')->onDelete('cascade');
            $table->foreignId('purchase_invoice_item_id')->nullable()->constrained('purchase_invoice_items')->onDelete('set null');
            $table->foreignId('linked_invoice_line_id')->nullable()->constrained('purchase_invoice_items')->onDelete('set null');
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->onDelete('set null');
            $table->foreignId('warehouse_id')->nullable()->constrained('inventory_locations')->onDelete('set null');
            
            // Item details
            $table->string('item_name');
            $table->string('item_code')->nullable();
            $table->text('description')->nullable();
            $table->string('unit_of_measure')->nullable();
            
            // Quantities and prices
            $table->decimal('quantity', 10, 2);
            $table->decimal('original_quantity', 10, 2)->nullable();
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('original_unit_cost', 15, 2)->nullable();
            $table->decimal('line_total', 15, 2)->default(0);
            
            // Cost tracking
            $table->decimal('cogs_cost_at_purchase', 15, 2)->nullable();
            $table->decimal('current_avg_cost', 15, 2)->nullable();
            
            // Return options
            $table->boolean('return_to_stock')->default(true);
            $table->enum('return_condition', ['resellable', 'damaged', 'scrap', 'refurbish'])->default('resellable');
            
            // Account references
            $table->foreignId('expense_account_id')->nullable()->constrained('chart_accounts')->onDelete('set null');
            $table->foreignId('cogs_account_id')->nullable()->constrained('chart_accounts')->onDelete('set null');
            
            // VAT and Tax
            $table->enum('vat_type', ['inclusive', 'exclusive', 'no_vat'])->default('inclusive');
            $table->decimal('vat_rate', 5, 2)->default(18.00);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->string('tax_code')->nullable();
            $table->json('tax_calculation_details')->nullable();
            
            // Discount
            $table->enum('discount_type', ['none', 'percentage', 'fixed'])->default('none');
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            
            // Restocking fees
            $table->decimal('restocking_fee_amount', 15, 2)->default(0);
            $table->decimal('restocking_fee_vat', 15, 2)->default(0);
            
            // FX
            $table->decimal('exchange_rate', 10, 6)->default(1.000000);
            $table->decimal('fx_gain_loss', 15, 2)->default(0);
            
            // Stock information
            $table->decimal('available_stock', 10, 2)->nullable();
            $table->decimal('reserved_stock', 10, 2)->nullable();
            $table->boolean('stock_available')->default(true);
            
            // Notes
            $table->text('notes')->nullable();
            $table->text('item_condition_notes')->nullable();
            $table->json('posting_details')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debit_note_items');
    }
};