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
        Schema::create('imprest_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('imprest_request_id');
            $table->string('journal_number')->unique();
            $table->enum('entry_type', ['issue', 'liquidation', 'replenishment', 'balance_return']);
            $table->unsignedBigInteger('debit_account_id');
            $table->unsignedBigInteger('credit_account_id');
            $table->decimal('amount', 15, 2);
            $table->text('description');
            $table->date('transaction_date');
            $table->string('reference_number')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('imprest_request_id')->references('id')->on('imprest_requests')->onDelete('cascade');
            $table->foreign('debit_account_id')->references('id')->on('chart_accounts');
            $table->foreign('credit_account_id')->references('id')->on('chart_accounts');
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['imprest_request_id']);
            $table->index(['entry_type']);
            $table->index(['transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imprest_journal_entries');
    }
};