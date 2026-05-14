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
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->string('pos_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->string('customer_name')->nullable();
            $table->datetime('sale_date');
            $table->string('terminal_id')->nullable();
            $table->foreignId('operator_id')->constrained('users')->onDelete('cascade');
            $table->enum('payment_method', ['cash', 'card', 'mobile_money', 'mixed'])->default('cash');
            $table->decimal('cash_amount', 15, 2)->default(0);
            $table->decimal('bank_amount', 15, 2)->default(0);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(18.00);
            $table->enum('vat_type', ['inclusive', 'exclusive', 'no_vat'])->default('inclusive');
            $table->decimal('withholding_tax_amount', 15, 2)->default(0);
            $table->decimal('withholding_tax_rate', 5, 2)->default(0);
            $table->enum('withholding_tax_type', ['percentage', 'fixed'])->default('percentage');
            $table->boolean('receipt_printed')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sales');
    }
};
