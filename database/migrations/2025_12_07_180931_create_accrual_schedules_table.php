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
        Schema::create('accrual_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_number')->unique();
            
            // Schedule Type and Nature
            $table->enum('schedule_type', ['prepayment', 'accrual']); // Prepayment / Accrual
            $table->enum('nature', ['expense', 'income']); // Expense / Income
            
            // Dates
            $table->date('start_date'); // Amortisation start date
            $table->date('end_date'); // Final amortisation period
            
            // Amounts
            $table->decimal('total_amount', 20, 2); // Amount to allocate
            $table->decimal('amortised_amount', 20, 2)->default(0); // Amount already amortised
            $table->decimal('remaining_amount', 20, 2); // Remaining unamortised balance
            
            // Accounts
            $table->foreignId('expense_income_account_id')->constrained('chart_accounts')->onDelete('restrict'); // P&L account
            $table->foreignId('balance_sheet_account_id')->constrained('chart_accounts')->onDelete('restrict'); // Prepaid, Accrued, Deferred, Accrued Income
            
            // Frequency
            $table->enum('frequency', ['monthly', 'quarterly', 'custom'])->default('monthly');
            $table->integer('custom_periods')->nullable(); // For custom frequency
            
            // Vendor/Customer (optional)
            $table->foreignId('vendor_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            
            // Currency and FX
            $table->string('currency_code', 3)->default('TZS');
            $table->decimal('fx_rate_at_creation', 15, 6)->default(1); // FX rate when schedule was created
            $table->decimal('home_currency_amount', 20, 2); // Amount in home currency
            
            // Description and Notes
            $table->text('description'); // Purpose of transaction
            $table->text('notes')->nullable();
            
            // Approval Fields
            $table->foreignId('prepared_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Status
            $table->enum('status', ['draft', 'submitted', 'approved', 'active', 'completed', 'cancelled'])->default('draft');
            
            // Company and Branch
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Locking
            $table->date('last_posted_period')->nullable(); // Last period that was posted (for period locking)
            $table->boolean('is_locked')->default(false); // Lock schedule from edits
            
            // Attachments
            $table->string('attachment_path')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['schedule_type', 'nature']);
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accrual_schedules');
    }
};
