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
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->string('cheque_number')->unique();
            $table->date('cheque_date');
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->onDelete('cascade');
            $table->string('payee_name');
            $table->decimal('amount', 20, 2);
            $table->enum('status', ['issued', 'cleared', 'bounced', 'cancelled', 'stale'])->default('issued');
            
            // Payment reference
            $table->string('payment_reference_type')->nullable(); // payment, purchase_invoice, etc.
            $table->unsignedBigInteger('payment_reference_id')->nullable();
            $table->string('payment_reference_number')->nullable(); // PV number, invoice number, etc.
            
            // Module origin tracking
            $table->string('module_origin')->nullable(); // ap, ar, imprest, expense_claim, payroll, etc.
            $table->string('payment_type')->nullable(); // supplier, petty_cash, imprest, refund, etc.
            
            // Cheque details
            $table->text('description')->nullable();
            $table->string('signature_authorization')->nullable(); // Digital signature path
            $table->string('cheque_template')->nullable(); // Bank template used for printing
            
            // Status tracking
            $table->date('cleared_date')->nullable();
            $table->date('bounced_date')->nullable();
            $table->date('cancelled_date')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('bounce_reason')->nullable();
            
            // Audit trail
            $table->foreignId('issued_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('cleared_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('printed_at')->nullable();
            $table->boolean('is_printed')->default(false);
            $table->boolean('is_voided')->default(false);
            $table->text('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('voided_at')->nullable();
            
            // Company and branch
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Journal entries
            $table->unsignedBigInteger('issue_journal_id')->nullable(); // Journal when cheque issued
            $table->unsignedBigInteger('clear_journal_id')->nullable(); // Journal when cheque cleared
            $table->unsignedBigInteger('bounce_journal_id')->nullable(); // Journal when cheque bounced
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('cheque_number');
            $table->index('bank_account_id');
            $table->index('status');
            $table->index(['payment_reference_type', 'payment_reference_id']);
            $table->index('company_id');
            $table->index('cheque_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
