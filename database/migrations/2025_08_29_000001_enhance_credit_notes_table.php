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
        Schema::table('credit_notes', function (Blueprint $table) {
            // Enhanced type enum to include all scenarios
            $table->enum('type', [
                'return', 
                'discount', 
                'correction', 
                'overbilling', 
                'service_adjustment', 
                'post_invoice_discount',
                'refund',
                'restocking_fee',
                'scrap_writeoff',
                'advance_refund',
                'fx_adjustment',
                'other'
            ])->default('return')->change();
            
            // Enhanced status enum
            $table->enum('status', [
                'draft', 
                'issued', 
                'applied', 
                'refunded', 
                'cancelled',
                'pending_approval'
            ])->default('draft')->change();
            
            // New fields for comprehensive scenarios
            $table->enum('reason_code', [
                'quality_issue',
                'price_adjustment', 
                'duplicate_billing',
                'order_cancellation',
                'service_quality',
                'overbilling',
                'promotional_discount',
                'volume_discount',
                'damaged_goods',
                'wrong_item',
                'late_delivery',
                'sla_penalty',
                'commercial_goodwill',
                'tax_correction',
                'fx_adjustment',
                'other'
            ])->nullable()->after('reason');
            
            // Settlement and refund options
            $table->boolean('refund_now')->default(false)->after('reason_code');
            $table->boolean('return_to_stock')->default(true)->after('refund_now');
            $table->decimal('restocking_fee_percentage', 5, 2)->nullable()->after('return_to_stock');
            $table->decimal('restocking_fee_amount', 15, 2)->default(0)->after('restocking_fee_percentage');
            $table->decimal('restocking_fee_vat', 15, 2)->default(0)->after('restocking_fee_amount');
            
            // Currency and FX fields
            $table->string('currency', 3)->default('TZS')->after('restocking_fee_vat');
            $table->decimal('exchange_rate', 15, 6)->default(1.000000)->after('currency');
            $table->decimal('fx_gain_loss', 15, 2)->default(0)->after('exchange_rate');
            
            // Reference fields
            $table->foreignId('reference_invoice_id')->nullable()->constrained('sales_invoices')->after('sales_invoice_id');
            $table->string('reference_document')->nullable()->after('reference_invoice_id');
            
            // Warehouse and inventory fields
            $table->foreignId('warehouse_id')->nullable()->constrained('inventory_locations')->after('branch_id');
            
            // Approval workflow
            $table->text('approval_notes')->nullable()->after('approved_at');
            $table->timestamp('submitted_at')->nullable()->after('approval_notes');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->after('submitted_at');
            
            // Audit and compliance
            $table->json('tax_calculation_details')->nullable()->after('approval_notes');
            $table->json('posting_details')->nullable()->after('tax_calculation_details');
            $table->string('document_series')->default('CN')->after('credit_note_number');
            
            // Additional amount fields
            $table->decimal('original_amount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('net_credit_amount', 15, 2)->default(0)->after('original_amount');
            $table->decimal('gross_credit_amount', 15, 2)->default(0)->after('net_credit_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropForeign(['reference_invoice_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['submitted_by']);
            
            $table->dropColumn([
                'reason_code',
                'refund_now',
                'return_to_stock',
                'restocking_fee_percentage',
                'restocking_fee_amount',
                'restocking_fee_vat',
                'currency',
                'exchange_rate',
                'fx_gain_loss',
                'reference_invoice_id',
                'reference_document',
                'warehouse_id',
                'approval_notes',
                'submitted_at',
                'submitted_by',
                'tax_calculation_details',
                'posting_details',
                'document_series',
                'original_amount',
                'net_credit_amount',
                'gross_credit_amount'
            ]);
            
            // Revert enum changes
            $table->enum('type', ['return', 'discount', 'correction', 'other'])->default('return')->change();
            $table->enum('status', ['draft', 'issued', 'applied', 'cancelled'])->default('draft')->change();
        });
    }
}; 