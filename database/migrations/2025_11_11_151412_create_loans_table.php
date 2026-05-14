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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('loan_number')->unique();
            $table->string('reference_no')->nullable();
            
            // Bank/Lender Information
            $table->unsignedBigInteger('bank_account_id')->nullable(); // Bank account from which loan is received
            $table->string('bank_name')->nullable();
            $table->string('bank_contact')->nullable();
            
            // Loan Details
            $table->decimal('principal_amount', 15, 2);
            $table->date('disbursement_date')->nullable();
            $table->decimal('interest_rate', 5, 2); // Annual interest rate in percentage
            $table->enum('rate_type', ['fixed', 'variable'])->default('fixed');
            $table->enum('calculation_basis', ['30/360', 'actual/365', 'actual/360'])->default('actual/365');
            $table->enum('payment_frequency', ['monthly', 'quarterly', 'semi-annual', 'annual'])->default('monthly');
            $table->integer('term_months');
            $table->date('first_payment_date')->nullable();
            $table->enum('amortization_method', ['annuity', 'straight_principal', 'interest_only'])->default('annuity');
            $table->integer('grace_period_months')->default(0);
            $table->decimal('effective_interest_rate', 5, 2)->nullable(); // For IFRS amortization
            
            // Status
            $table->enum('status', ['draft', 'approved', 'disbursed', 'active', 'closed', 'restructured'])->default('draft');
            
            // GL Account Mappings
            $table->unsignedBigInteger('loan_payable_account_id')->nullable(); // Chart account for loan liability
            $table->unsignedBigInteger('interest_expense_account_id')->nullable();
            $table->unsignedBigInteger('interest_payable_account_id')->nullable();
            $table->unsignedBigInteger('deferred_loan_costs_account_id')->nullable();
            $table->unsignedBigInteger('bank_charges_account_id')->nullable();
            
            // Currency
            $table->unsignedBigInteger('currency_id')->nullable();
            
            // Tracking
            $table->decimal('total_interest_paid', 15, 2)->default(0);
            $table->decimal('total_principal_paid', 15, 2)->default(0);
            $table->decimal('outstanding_principal', 15, 2)->default(0);
            $table->decimal('accrued_interest', 15, 2)->default(0);
            
            // Metadata
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            $table->foreign('loan_payable_account_id')->references('id')->on('chart_accounts')->onDelete('set null');
            $table->foreign('interest_expense_account_id')->references('id')->on('chart_accounts')->onDelete('set null');
            $table->foreign('interest_payable_account_id')->references('id')->on('chart_accounts')->onDelete('set null');
            $table->foreign('deferred_loan_costs_account_id')->references('id')->on('chart_accounts')->onDelete('set null');
            $table->foreign('bank_charges_account_id')->references('id')->on('chart_accounts')->onDelete('set null');
            
            // Indexes
            $table->index(['company_id', 'branch_id']);
            $table->index('status');
            $table->index('disbursement_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
