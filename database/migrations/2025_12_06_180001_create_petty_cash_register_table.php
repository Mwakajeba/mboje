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
        if (!Schema::hasTable('petty_cash_register')) {
            Schema::create('petty_cash_register', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('petty_cash_unit_id');
            $table->unsignedBigInteger('petty_cash_transaction_id')->nullable();
            $table->unsignedBigInteger('petty_cash_replenishment_id')->nullable();
            $table->unsignedBigInteger('imprest_request_id')->nullable(); // For sub-imprest mode
            $table->date('register_date');
            $table->string('pcv_number')->nullable(); // Petty Cash Voucher number
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->enum('entry_type', ['disbursement', 'replenishment', 'opening_balance', 'adjustment'])->default('disbursement');
            $table->enum('nature', ['debit', 'credit'])->default('debit');
            $table->unsignedBigInteger('gl_account_id')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->enum('status', ['pending', 'approved', 'posted', 'rejected'])->default('pending');
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('petty_cash_unit_id')->references('id')->on('petty_cash_units')->onDelete('cascade');
            $table->foreign('petty_cash_transaction_id')->references('id')->on('petty_cash_transactions')->onDelete('set null');
            $table->foreign('petty_cash_replenishment_id')->references('id')->on('petty_cash_replenishments')->onDelete('set null');
            $table->foreign('imprest_request_id')->references('id')->on('imprest_requests')->onDelete('set null');
            $table->foreign('gl_account_id')->references('id')->on('chart_accounts')->onDelete('set null');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['petty_cash_unit_id', 'register_date'], 'pcr_unit_date_idx');
            $table->index(['pcv_number'], 'pcr_pcv_number_idx');
            $table->index(['status'], 'pcr_status_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_register');
    }
};


