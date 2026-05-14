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
        if (!Schema::hasTable('account_transfers')) {
            Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->string('transfer_number')->unique();
            $table->date('transfer_date');
            $table->enum('from_account_type', ['bank', 'cash', 'petty_cash']);
            $table->unsignedBigInteger('from_account_id'); // Polymorphic: bank_account_id, cash_account_id, or petty_cash_unit_id
            $table->enum('to_account_type', ['bank', 'cash', 'petty_cash']);
            $table->unsignedBigInteger('to_account_id'); // Polymorphic
            $table->decimal('amount', 15, 2);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);
            $table->decimal('amount_fcy', 15, 2)->nullable(); // Foreign currency amount
            $table->decimal('charges', 15, 2)->default(0);
            $table->foreignId('charges_account_id')->nullable()->constrained('chart_accounts')->onDelete('set null');
            $table->text('description');
            $table->text('reference_number')->nullable(); // EFT reference, cheque number, etc.
            $table->string('attachment')->nullable(); // Banking documents
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'posted'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'branch_id'], 'at_company_branch_idx');
            $table->index(['from_account_type', 'from_account_id'], 'at_from_account_idx');
            $table->index(['to_account_type', 'to_account_id'], 'at_to_account_idx');
            $table->index('status', 'at_status_idx');
            $table->index('transfer_date', 'at_date_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_transfers');
    }
};

