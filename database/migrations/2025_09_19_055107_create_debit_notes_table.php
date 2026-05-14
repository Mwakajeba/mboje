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
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('debit_note_number')->unique();
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->onDelete('cascade');
            $table->foreignId('reference_invoice_id')->nullable()->constrained('purchase_invoices')->onDelete('set null');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->date('debit_note_date');
            $table->enum('status', ['draft', 'issued', 'approved', 'applied', 'cancelled'])->default('draft');
            $table->enum('type', ['return', 'discount', 'correction', 'overbilling', 'service_adjustment', 'post_invoice_discount', 'refund', 'restocking_fee', 'scrap_writeoff', 'advance_refund', 'fx_adjustment', 'other'])->default('return');
            $table->string('reason_code')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            
            // Refund and return options
            $table->boolean('refund_now')->default(false);
            $table->boolean('return_to_stock')->default(true);
            $table->decimal('restocking_fee_percentage', 5, 2)->default(0);
            $table->decimal('restocking_fee_amount', 15, 2)->default(0);
            $table->decimal('restocking_fee_vat', 15, 2)->default(0);
            
            // Currency and FX
            $table->string('currency', 3)->default('TZS');
            $table->decimal('exchange_rate', 10, 6)->default(1.000000);
            $table->decimal('fx_gain_loss', 15, 2)->default(0);
            $table->string('reference_document')->nullable();
            
            // Warehouse
            $table->foreignId('warehouse_id')->nullable()->constrained('inventory_locations')->onDelete('set null');
            
            // Approval workflow
            $table->text('approval_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Tax calculation details
            $table->json('tax_calculation_details')->nullable();
            $table->json('posting_details')->nullable();
            $table->string('document_series')->nullable();
            
            // Amount fields
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('original_amount', 15, 2)->default(0);
            $table->decimal('net_debit_amount', 15, 2)->default(0);
            $table->decimal('gross_debit_amount', 15, 2)->default(0);
            $table->decimal('applied_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            
            // VAT and Tax fields
            $table->decimal('vat_rate', 5, 2)->default(18.00);
            $table->enum('vat_type', ['inclusive', 'exclusive'])->default('inclusive');
            
            // Branch and user tracking
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debit_notes');
    }
};