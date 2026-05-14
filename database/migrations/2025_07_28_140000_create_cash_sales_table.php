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
        Schema::create('cash_sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->date('sale_date');
            $table->enum('payment_method', ['cash', 'bank', 'cash_deposit'])->default('cash');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->onDelete('set null');
            $table->foreignId('cash_deposit_id')->nullable()->constrained('cash_deposits')->onDelete('set null');
            
            // Amount fields
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('TZS');
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);
            
            // VAT and Tax fields
            $table->decimal('vat_rate', 5, 2)->default(18.00);
            $table->enum('vat_type', ['inclusive', 'exclusive', 'no_vat'])->default('no_vat');
            $table->decimal('withholding_tax_amount', 15, 2)->default(0);
            $table->decimal('withholding_tax_rate', 5, 2)->default(0);
            $table->enum('withholding_tax_type', ['percentage', 'fixed'])->default('percentage');
            
            // Notes and terms
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            
            // Branch and user tracking
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            // $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_sales');
    }
}; 