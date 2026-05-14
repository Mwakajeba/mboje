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
        Schema::table('credit_note_items', function (Blueprint $table) {
            // Cost tracking for inventory returns
            $table->decimal('cogs_cost_at_sale', 15, 2)->nullable()->after('line_total');
            $table->decimal('current_avg_cost', 15, 2)->nullable()->after('cogs_cost_at_sale');
            
            // Warehouse and inventory tracking
            $table->foreignId('warehouse_id')->nullable()->constrained('inventory_locations')->after('inventory_item_id');
            $table->boolean('return_to_stock')->default(true)->after('warehouse_id');
            $table->enum('return_condition', ['resellable', 'damaged', 'scrap', 'refurbish'])->default('resellable')->after('return_to_stock');
            
            // Tax and accounting details
            $table->foreignId('revenue_account_id')->nullable()->constrained('chart_accounts')->after('return_condition');
            $table->foreignId('cogs_account_id')->nullable()->constrained('chart_accounts')->after('revenue_account_id');
            $table->string('tax_code')->nullable()->after('cogs_account_id');
            $table->json('tax_calculation_details')->nullable()->after('tax_code');
            
            // Reference tracking
            $table->foreignId('linked_invoice_line_id')->nullable()->constrained('sales_invoice_items')->after('sales_invoice_item_id');
            $table->decimal('original_quantity', 15, 2)->nullable()->after('quantity');
            $table->decimal('original_unit_price', 15, 2)->nullable()->after('original_quantity');
            
            // Restocking fee for this item
            $table->decimal('restocking_fee_amount', 15, 2)->default(0)->after('discount_amount');
            $table->decimal('restocking_fee_vat', 15, 2)->default(0)->after('restocking_fee_amount');
            
            // FX and currency
            $table->decimal('exchange_rate', 15, 6)->default(1.000000)->after('restocking_fee_vat');
            $table->decimal('fx_gain_loss', 15, 2)->default(0)->after('exchange_rate');
            
            // Additional tracking
            $table->string('item_condition_notes')->nullable()->after('notes');
            $table->json('posting_details')->nullable()->after('item_condition_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['revenue_account_id']);
            $table->dropForeign(['cogs_account_id']);
            $table->dropForeign(['linked_invoice_line_id']);
            
            $table->dropColumn([
                'cogs_cost_at_sale',
                'current_avg_cost',
                'warehouse_id',
                'return_to_stock',
                'return_condition',
                'revenue_account_id',
                'cogs_account_id',
                'tax_code',
                'tax_calculation_details',
                'linked_invoice_line_id',
                'original_quantity',
                'original_unit_price',
                'restocking_fee_amount',
                'restocking_fee_vat',
                'exchange_rate',
                'fx_gain_loss',
                'item_condition_notes',
                'posting_details'
            ]);
        });
    }
}; 