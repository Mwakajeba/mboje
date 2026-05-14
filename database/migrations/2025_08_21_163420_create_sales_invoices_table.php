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
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->onDelete('set null');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->enum('payment_terms', ['immediate', 'net_15', 'net_30', 'net_45', 'net_60', 'custom'])->default('net_30');
            $table->integer('payment_days')->default(30);
            
            // Amount fields
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            
            // VAT and Tax fields
            $table->decimal('vat_rate', 5, 2)->default(18.00);
            $table->enum('vat_type', ['inclusive', 'exclusive'])->default('inclusive');
            $table->decimal('withholding_tax_amount', 15, 2)->default(0);
            $table->decimal('withholding_tax_rate', 5, 2)->default(0);
            $table->enum('withholding_tax_type', ['percentage', 'fixed'])->default('percentage');
            
            $table->boolean('early_payment_discount_enabled')->default(false);
            $table->enum('early_payment_discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('early_payment_discount_rate', 10, 2)->default(0);
            $table->integer('early_payment_days')->default(0);
            $table->boolean('late_payment_fees_enabled')->default(false);
            $table->enum('late_payment_fees_type', ['monthly', 'one_time'])->default('monthly');
            $table->decimal('late_payment_fees_rate', 10, 2)->default(0);
            // Notes and terms
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            
            // Branch and user tracking
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['branch_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['invoice_date', 'status']);
            $table->index('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
