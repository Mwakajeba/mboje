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
        if (Schema::hasTable('petty_cash_transactions')) {
            return; // Table already exists, skip migration
        }
        
        Schema::create('petty_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_cash_unit_id')->constrained('petty_cash_units')->onDelete('restrict');
            $table->string('transaction_number')->unique();
            $table->date('transaction_date');
            $table->foreignId('expense_category_id')->constrained('petty_cash_expense_categories')->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->string('payee')->nullable(); // Who received the payment
            $table->text('description');
            $table->text('notes')->nullable();
            $table->string('receipt_attachment')->nullable(); // Path to scanned receipt
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'posted'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->decimal('balance_after', 15, 2)->nullable(); // Balance after this transaction
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['petty_cash_unit_id', 'transaction_date'], 'pct_unit_date_idx');
            $table->index('status', 'pct_status_idx');
            $table->index('created_by', 'pct_created_by_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_transactions');
    }
};

